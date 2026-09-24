<?php

namespace Croogo\FileManager\Controller\Admin;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Croogo\Core\Croogo;
use Croogo\FileManager\Model\Table\AttachmentFoldersTable;
use Exception;

/**
 * Attachments Controller
 *
 * This file will take care of file uploads (with rich text editor integration).
 *
 * @category Assets.Controller
 * @package  Assets.Controller
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @author   Rachman Chavik <contact@xintesa.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class AttachmentsController extends AppController
{

    /**
     * Helpers used by the Controller
     *
     * @var array
     * @access public
     */
    public array $paginate = [
        'limit' => 5,
    ];

    public function initialize(): void
    {
        parent::initialize();
        // Cake 4.5+: $helpers jako właściwość kontrolera usunięte -> setHelpers().
        $this->viewBuilder()->setHelpers([
            'Croogo/FileManager.AssetsImage',
            'Croogo/FileManager.FileManager',
            'Text',
        ]);
        // Search 6: PrgComponent scalony w SearchComponent (Search.Search).
        $this->loadComponent('Search.Search', [
            'actions' => [
                'index', 'browse', 'listings',
            ],
        ]);

        $this->_loadCroogoComponents(['BulkProcess']);
        $this->Attachments = $this->fetchTable('Croogo/FileManager.Attachments');
    }

    /**
     * Before executing controller actions
     *
     * @return void
     * @access public
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        if ($this->getRequest()->getParam('action') == 'resize') {
            $this->FormProtection->setConfig('validate', false);
        }
    }

    /**
     * Admin index
     *
     * @return void
     * @access public
     */
    public function index(): void
    {
        $this->set('title_for_layout', __d('croogo', 'Attachments'));

        $this->set('searchFields', [
            'search',
            'model' => [
                'type' => 'hidden',
            ],
            'foreign_key' => [
                'type' => 'hidden',
            ],
            'all' => [
                'type' => 'hidden',
            ],
        ]);

        $query = $this->Attachments->find();

        $isChooser = false;

        if ($this->getRequest()->getQuery('links') || $this->getRequest()->getQuery('chooser')) {
            $isChooser = true;
        }

        $model = $this->getRequest()->getQuery('model');
        $foreignKey = $this->getRequest()->getQuery('foreign_key');
        $this->set(compact('model', 'foreignKey'));
        $httpQuery = (array)$this->getRequest()->getQuery();

        if ($this->getRequest()->getQuery('manage')) {
            $finder = 'versions';
            unset($httpQuery['model']);
            unset($httpQuery['foreign_key']);
        } elseif (isset($httpQuery['asset_id']) ||
            isset($httpQuery['all'])
        ) {
            $finder = 'versions';
            unset($httpQuery['model']);
            unset($httpQuery['foreign_key']);

            if (!$this->getRequest()->getQuery('sort')) {
                $query->orderBy([
                    $this->Attachments->aliasField('id') => 'desc',
                ]);
            }
        } elseif ($this->getRequest()->getQuery('search')) {
            $finder = null;
        } else {
            if (empty($model) || empty($foreignKey)) {
                $finder = 'versions';
            } else {
                $finder = 'modelAttachments';
            }
            $query->where([
                'Assets.parent_asset_id IS' => null,
            ]);
        }

        if (!$this->getRequest()->getQuery('sort')) {
            $query->orderBy(['Attachments.created' => 'DESC']);
        }

        $this->applyFolder($query);

        if ($isChooser) {
            if ($this->getRequest()->getQuery('chooser_type') == 'image') {
                $query->where([
                    'Assets.mime_type LIKE' => 'image/%',
                ]);
            } else {
                $query->where([
                    'Assets.mime_type NOT LIKE' => 'image/%',
                ]);
            }
        }

        // Cake 5.3 no longer maps an options array onto finder arguments, so
        // the old `find('search', ['search' => ...])` silently filtered nothing.
        $query->find('search', search: $httpQuery);

        if (isset($finder)) {
            $query->find($finder);
        }

        // Cake 5: formatResults przyjmuje tylko Closure
        $query->formatResults($this->Attachments->getVideoPoster(...));

        $this->set('attachments', $this->paginate($query));

        if ($this->getRequest()->getQuery('links') || $this->getRequest()->getQuery('chooser')) {
            $this->viewBuilder()->setLayout('admin_popup');
            $this->render('chooser');
        }
    }

    /**
     * Folder browsing for the library list and the chooser.
     *
     * Only the plain library view is scoped to a folder. Lists opened for a
     * specific record (model/foreign_key), a single asset's versions
     * (asset_id/manage), `all`, and the editor's browse popup keep showing
     * everything, as before folders existed - otherwise files put in a folder
     * would silently vanish from them.
     */
    protected function applyFolder($query): void
    {
        $request = $this->getRequest();
        $scoped = $request->getParam('action') === 'index';
        foreach (['model', 'foreign_key', 'asset_id', 'manage', 'all'] as $param) {
            if ($request->getQuery($param)) {
                $scoped = false;
            }
        }
        $this->set('folderBrowsing', $scoped);
        if (!$scoped) {
            return;
        }

        $folders = $this->fetchTable('Croogo/FileManager.AttachmentFolders');
        $folderId = AttachmentFoldersTable::normalizeId($request->getQuery('folder_id'));
        if ($folderId !== null && !$folders->exists(['id' => $folderId])) {
            // The chooser reopens the last folder it remembers, which may have
            // been deleted since; land on the root instead of a dead end.
            if (!$request->getQuery('chooser') && !$request->getQuery('links')) {
                throw new NotFoundException(__d('croogo', 'Folder not found'));
            }
            $folderId = null;
        }

        $allFolders = (bool)$request->getQuery('all_folders');
        if (!$allFolders) {
            $query->find('inFolder', folder: $folderId);
        }

        // Search stays in the current folder unless asked otherwise.
        $this->set('searchFields', (array)$this->viewBuilder()->getVar('searchFields') + [
            'folder_id' => [
                'type' => 'hidden',
            ],
            'all_folders' => [
                'type' => 'checkbox',
                'label' => __d('croogo', 'In all folders'),
                'hiddenField' => false,
                'value' => 1,
                'checked' => $allFolders,
            ],
        ]);

        $tree = $folders->tree();
        $this->set([
            'folderId' => $folderId,
            'allFolders' => $allFolders,
            'folderTree' => $tree['folders'],
            'rootFolderCount' => $tree['rootCount'],
            'folderPath' => $folders->pathTo($folderId),
            'folderOptions' => $folders->options(),
        ]);
    }

    /**
     * Admin add
     *
     * @return void
     * @access public
     */
    public function add()
    {
        $this->set('title_for_layout', __d('croogo', 'Add Attachment'));

        if ($this->getRequest()->getQuery('editor')) {
            $this->viewBuilder()->setLayout('admin_popup');
        }

        $folderId = AttachmentFoldersTable::normalizeId($this->getRequest()->getQuery('folder_id'));

        if ($this->getRequest()->is('post')) {
            $data = $this->getRequest()->getData();
            if (!empty($data)) {
                if (array_key_exists('folder_id', $data)) {
                    $data['folder_id'] = AttachmentFoldersTable::normalizeId($data['folder_id']);
                    $folderId = $data['folder_id'];
                }
                $entity = $this->Attachments->newEntity($data);
                $errors = $entity->getErrors();
            } else {
                $errors = [
                    'file' => __d('croogo', 'Upload failed. Please ensure size does not exceed the server limit.')
                ];
            }

            if (empty($errors)) {
                $attachment = $this->Attachments->save($entity);

                $errors = $entity->getErrors();
                if (empty($errors) && $attachment) {
                    $eventKey = 'Controller.FileManager/Attachment.newAttachment';
                    Croogo::dispatchEvent($eventKey, $this, compact('attachment'));
                } else {
                    Log::error('Failed saving attachments:');
                    Log::error(print_r($errors, true));
                }
            } else {
                Log::error('Failed validating attachments:');
                Log::error(print_r($errors, true));
            }

            if ($this->getRequest()->is('ajax')) {
                $files = [];
                $error = false;

                if (empty($errors)) {
                    $this->viewBuilder()->setClassName('Json');
                    $files = [[
                        'url' => $attachment->asset->path,
                        'thumbnail_url' => $attachment->asset->path,
                        'name' => $attachment->title,
                        'type' => $attachment->asset->mime_type,
                        'size' => $attachment->asset->filesize,
                    ]];
                } else {
                    $error = implode("\n", Hash::flatten($errors));
                    $files = [[
                        'error' => $error,
                    ]];
                }

                $this->set(compact('files', 'error'));
                $this->viewBuilder()->setOption('serialize', ['files', 'error']);

                return;
            } else {
                // noop
            }

            if ($attachment) {
                $this->Flash->success(__d('croogo', 'The Attachment has been saved'));
                $url = [];
                if (isset($saved->asset->asset_usage[0])) {
                    $usage = $saved->asset->asset_usage[0];
                    if (!empty($usage->model) && !empty($usage->foreign_key)) {
                        $url['?']['model'] = $usage->model;
                        $url['?']['foreign_key'] = $usage->foreign_key;
                    }
                }
                if ($this->getRequest()->getQuery('editor')) {
                    $url = array_merge($url, ['action' => 'browse']);
                } else {
                    $url = array_merge($url, ['action' => 'index']);
                    if ($folderId !== null) {
                        $url['?']['folder_id'] = $folderId;
                    }
                }

                return $this->redirect($url);
            } else {
                $this->Flash->error(__d('croogo', 'The Attachment could not be saved. Please, try again.'));
            }
        } else {
            // noop
        }

        $attachment = $this->Attachments->newEmptyEntity();
        $attachment->folder_id = $folderId;
        $this->set(compact('attachment', 'folderId'));
        $this->set('folderOptions', $this->Attachments->AttachmentFolders->options());
        $this->set('folderPath', $this->Attachments->AttachmentFolders->pathTo($folderId));
    }

    /**
     * Admin edit
     *
     * @param int $id
     * @return \Cake\Http\Response|void
     * @access public
     */
    public function edit($id = null)
    {
        $this->set('title_for_layout', __d('croogo', 'Edit Attachment'));

        if ($this->getRequest()->getQuery('editor')) {
            $this->layout = 'admin_popup';
        }

        $redirect = ['action' => 'index'];
        if (!empty($this->getRequest()->getQuery())) {
            $redirect = array_merge(
                $redirect,
                ['action' => 'browse', '?' => $this->getRequest()->getQuery()]
            );
        }

        if (!$id && empty($this->getRequest()->getData())) {
            $this->Flash->error(__d('croogo', 'Invalid Attachment'));

            return $this->redirect($redirect);
        }
        $attachment = $this->Attachments->get($id, [
            'contain' => [
                'Assets',
            ],
        ]);
        if (!empty($this->getRequest()->getData())) {
            $data = $this->getRequest()->getData();
            if (array_key_exists('folder_id', $data)) {
                $data['folder_id'] = AttachmentFoldersTable::normalizeId($data['folder_id']);
            }
            $attachment = $this->Attachments->patchEntity($attachment, $data);
            if ($this->Attachments->save($attachment)) {
                $this->Flash->success(__d('croogo', 'The Attachment has been saved'));

                $redirect = $this->getRequest()->getQuery('redirect') ?: [
                    'action' => 'index',
                ];
                if (is_array($redirect) && $attachment->folder_id !== null) {
                    $redirect['?']['folder_id'] = $attachment->folder_id;
                }

                return $this->redirect($redirect);
            } else {
                $this->Flash->error(__d('croogo', 'The Attachment could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('attachment'));
        $this->set('folderOptions', $this->Attachments->AttachmentFolders->options());
    }

    /**
     * Admin delete
     *
     * @param int $id
     * @return \Cake\Http\Response|void
     * @access public
     */
    public function delete($id = null)
    {
        if (!$id) {
            $this->Flash->error(__d('croogo', 'Invalid id for Attachment'));

            return $this->redirect(['action' => 'index']);
        }

        $redirect = $this->referer(['action' => 'index'], true);

        $attachment = $this->Attachments->get($id);
        $this->Attachments->getConnection()->begin();
        if ($this->Attachments->delete($attachment)) {
            $this->Attachments->getConnection()->commit();
            $this->Flash->success(__d('croogo', 'Attachment deleted'));

            return $this->redirect($redirect);
        }

        $this->Flash->error(__d('croogo', 'Invalid id for Attachment'));

        return $this->redirect($redirect);
    }

    /**
     * Admin browse
     *
     * @return void
     * @access public
     */
    public function browse(): void
    {
        $this->viewBuilder()->setLayout('admin_popup');
        $this->index();
    }

    public function listing(): void
    {
        if ($this->getRequest()->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
            $this->paginate['limit'] = 100;
        }

        $query = $this->Attachments
            ->find('search', search: (array)$this->getRequest()->getQuery())
            ->find('modelAttachments');
        $attachments = $this->paginate($query);
        $this->set(compact('attachments'));
    }

    public function resize($id = null)
    {
        if (empty($id)) {
            throw new NotFoundException('Missing Asset Id to resize');
        }

        $result = false;
        if (!empty($this->getRequest()->getData('width'))) {
            $width = $this->getRequest()->getData('width');
            try {
                $result = $this->Attachments->createResized($id, $width, null);
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
        }

        $this->set(compact('result'));
        $this->viewBuilder()->setOption('serialize', 'result');
    }

    public function process()
    {
        $Attachments = $this->Attachments;
        list($action, $ids) = $this->BulkProcess->getRequestVars($Attachments->getAlias());

        $messageMap = [
            'delete' => __d('croogo', 'Attachments deleted'),
        ];

        // Back to the folder the list was showing, not to the root.
        $redirect = ['action' => 'index'];
        $currentFolder = AttachmentFoldersTable::normalizeId($this->getRequest()->getData('current_folder_id'));
        if ($currentFolder !== null) {
            $redirect['?']['folder_id'] = $currentFolder;
        }

        if ($action === 'move') {
            return $this->processMove($ids, $redirect);
        }

        return $this->BulkProcess->process($Attachments, $action, $ids, [
            'messageMap' => $messageMap,
            'redirect' => $redirect,
        ]);
    }

    /**
     * Bulk "Move to folder". Not routed through BulkProcessBehavior because the
     * behavior's actions only receive the ids, and this one needs a target.
     */
    protected function processMove(array $ids, array $redirect)
    {
        if (!$ids) {
            $this->Flash->error(__d('croogo', 'No item selected'));

            return $this->redirect($redirect);
        }

        $target = AttachmentFoldersTable::normalizeId($this->getRequest()->getData('target_folder_id'));
        try {
            $moved = $this->Attachments->moveToFolder($ids, $target);
        } catch (\InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());

            return $this->redirect($redirect);
        }

        $this->Flash->success(__dn(
            'croogo',
            '%d attachment moved',
            '%d attachments moved',
            $moved,
            $moved
        ));

        return $this->redirect($redirect);
    }
}
