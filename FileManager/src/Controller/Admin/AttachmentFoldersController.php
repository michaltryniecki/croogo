<?php

namespace Croogo\FileManager\Controller\Admin;

use Cake\Http\Response;
use Croogo\FileManager\Model\Table\AttachmentFoldersTable;

/**
 * Attachment Folders Controller
 *
 * Folders are browsed from the attachment list (Media > Attachments); this
 * controller only creates, renames/moves and deletes them, and always sends
 * the user back to the attachment list opened on the relevant folder.
 *
 * @property \Croogo\FileManager\Model\Table\AttachmentFoldersTable $AttachmentFolders
 */
class AttachmentFoldersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->AttachmentFolders = $this->fetchTable('Croogo/FileManager.AttachmentFolders');
    }

    /**
     * There is no separate folder list: the tree lives next to the attachments.
     */
    public function index()
    {
        return $this->redirect($this->attachmentsUrl(
            AttachmentFoldersTable::normalizeId($this->getRequest()->getQuery('folder_id'))
        ));
    }

    /**
     * New folder, by default inside the folder the user is looking at.
     */
    public function add()
    {
        $folder = $this->AttachmentFolders->newEmptyEntity();
        $folder->parent_id = AttachmentFoldersTable::normalizeId($this->getRequest()->getQuery('parent_id'));

        if ($this->getRequest()->is('post')) {
            $folder = $this->AttachmentFolders->patchEntity($folder, $this->getRequest()->getData());
            $folder->parent_id = AttachmentFoldersTable::normalizeId($folder->parent_id);
            if ($this->AttachmentFolders->save($folder)) {
                $this->Flash->success(__d('croogo', 'The folder has been created'));

                return $this->redirect($this->attachmentsUrl($folder->id));
            }
            $this->Flash->error(__d('croogo', 'The folder could not be saved. Please, try again.'));
        }

        $this->set('title_for_layout', __d('croogo', 'New Folder'));
        $this->set('folder', $folder);
        $this->set('parentOptions', $this->AttachmentFolders->options());
        $this->set('path', $this->AttachmentFolders->pathTo($folder->parent_id));
        $this->render('form');
    }

    /**
     * Rename a folder or move it (with its whole subtree) under another parent.
     */
    public function edit($id = null)
    {
        $folder = $this->AttachmentFolders->get($id);

        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            $folder = $this->AttachmentFolders->patchEntity($folder, $this->getRequest()->getData());
            $folder->parent_id = AttachmentFoldersTable::normalizeId($folder->parent_id);
            if ($this->AttachmentFolders->save($folder)) {
                $this->Flash->success(__d('croogo', 'The folder has been saved'));

                return $this->redirect($this->attachmentsUrl($folder->id));
            }
            $this->Flash->error(__d('croogo', 'The folder could not be saved. Please, try again.'));
        }

        $this->set('title_for_layout', __d('croogo', 'Edit Folder'));
        $this->set('folder', $folder);
        $this->set('parentOptions', $this->AttachmentFolders->options($folder->id));
        $this->set('path', $this->AttachmentFolders->pathTo($folder->id));
        $this->render('form');
    }

    /**
     * Delete a folder; its files and subfolders move up to the parent folder.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $folder = $this->AttachmentFolders->get($id);
        if ($this->AttachmentFolders->deleteMovingContents($folder)) {
            $this->Flash->success(__d(
                'croogo',
                'Folder "%s" deleted. Its contents were moved to the parent folder.',
                $folder->name
            ));
        } else {
            $this->Flash->error(__d('croogo', 'The folder could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->attachmentsUrl($folder->parent_id));
    }

    protected function attachmentsUrl(?int $folderId): array
    {
        $url = [
            'plugin' => 'Croogo/FileManager',
            'controller' => 'Attachments',
            'action' => 'index',
        ];
        if ($folderId !== null) {
            $url['?'] = ['folder_id' => $folderId];
        }

        return $url;
    }
}
