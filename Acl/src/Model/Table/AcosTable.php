<?php

namespace Croogo\Acl\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * AclAco Model
 *
 * @category Model
 * @package  Croogo.Acl.Model
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class AcosTable extends \Acl\Model\Table\AcosTable
{

    /**
     * getChildren
     *
     * @param integer aco id
     */
    public function getChildren($acoId, $fields = [])
    {
        $fields = Hash::merge(['id', 'parent_id', 'alias'], $fields);
        $acos = $this->find('children', for: $acoId)
            ->find('threaded');

        return $acos;
    }

    /**
     * Create ACO tree
     */
    public function createFromPath($path)
    {
        $pathE = explode('/', $path);
        $parent = $current = null;
        foreach ($pathE as $alias) {
            $current[] = $alias;
            $node = $this->node(join('/', $current));
            if ($node) {
                $node = $node->toArray();
                $parent = $node[0];
            } else {
                if (!$parent) {
                    $parent = $this->find()
                        ->where([
                            $this->aliasField('alias') => 'controllers',
                        ])
                        ->first();

                    if (!$parent) {
                        $rootNode = $this->newEntity([
                            'alias' => 'controllers',
                        ]);
                        $parent = $this->save($rootNode);
                    }
                }
                $aco = $this->newEntity([
                    'parent_id' => $parent->id,
                    'alias' => $alias,
                ]);
                $parent = $this->save($aco);
            }
        }

        return $parent;
    }

    /**
     * ACL: add ACO
     *
     * Creates ACOs with permissions for roles.
     *
     * @param string $action possible values: Controller, Controller/action,
     *                                        Plugin/Controller/action
     * @param array $allowRoles Role aliases
     * @return void
     */
    public function addAco($action, $allowRoles = []): void
    {
        // AROs
        $roles = [];
        if (count($allowRoles) > 0) {
            $roles = \Cake\ORM\TableRegistry::getTableLocator()->get('Croogo/Users.Roles')->find('list',
            conditions: [
                'Roles.alias IN' => $allowRoles,
            ],
            fields: [
                'Roles.id',
                'Roles.alias',
            ])->toArray();
        }

        $this->createFromPath($action);
        $Permission = \Cake\ORM\TableRegistry::getTableLocator()->get('Croogo/Acl.Permissions');
        foreach ($roles as $roleId => $roleAlias) {
            $Permission->allow(['model' => 'Roles', 'foreign_key' => $roleId], $action);
        }
    }

    /**
     * ACL: remove ACO
     *
     * Removes ACOs and their Permissions
     *
     * @param string $action possible values: ControllerName, ControllerName/method_name
     * @return void
     */
    public function removeAco($action): void
    {
        $acoNodes = $this->node($action);
        if ($acoNodes) {
            $acoNode = $acoNodes->first();
            $this->delete($acoNode);
        }
    }

    /**
     * Get valid permission roots
     *
     * @return array Array of valid permission roots
     */
    public function getPermissionRoots()
    {
        $roots = $this->find('all',
        fields: ['id', 'alias'],
        conditions: [
            'parent_id IS' => null,
            'alias IN' => ['controllers', 'api'],
        ])->toArray();

        $apiRoot = -1;
        foreach ($roots as $i => &$root) {
            if ($root->alias === 'api') {
                $apiRoot = $root->id;
                $apiIndex = $i;
            }
            $root->title = ucfirst($root->alias);
        }
        if (isset($apiIndex)) {
            unset($roots[$apiIndex]);
        }

        $versionRoots = $this->find('all',
        fields: ['id', 'alias'],
        conditions: [
            'parent_id' => $apiRoot,
        ])->toArray();

        $apiCount = count($versionRoots);

        $api = __d('croogo', 'API');
        foreach ($versionRoots as &$versionRoot) {
            $alias = strtolower(str_replace('_', '.', $versionRoot->alias));
            $versionRoot->alias = $alias;
            $versionRoot->title = $apiCount == 1 ? $api : $api . ' ' . $alias;
        }

        return array_merge($roots, $versionRoots);
    }

    /**
     * Leaf ACOs (actions, or controllers without actions) whose parent_id path contains every
     * term of $search, ignoring case and reading `\` in a plugin alias as `/`
     *
     * One query, then the tree is walked in memory: a path query per node costs tens of ms on
     * the unindexed acos table, too slow for a search box.
     *
     * @param string $search Search terms
     * @param int $limit Maximum number of leaves listed
     * @return array{total: int, acos: array<int, string>} The count of all matching leaves, and the first $limit of them in tree order as id => path
     */
    public function searchLeaves(string $search, int $limit): array
    {
        $found = ['total' => 0, 'acos' => []];
        $terms = preg_split('/\s+/', $this->_searchable($search), -1, PREG_SPLIT_NO_EMPTY);
        if (!$terms) {
            return $found;
        }

        $nodes = $this->find()
            ->select(['id', 'parent_id', 'alias'])
            ->orderByAsc('lft')
            ->disableHydration()
            ->all()
            ->indexBy('id')
            ->toArray();
        $parents = array_flip(array_filter(array_column($nodes, 'parent_id')));

        foreach ($nodes as $id => $node) {
            if (isset($parents[$id])) {
                continue;
            }
            $path = $this->_aliasPath($id, $nodes);
            $searchable = $this->_searchable($path);
            foreach ($terms as $term) {
                if (!str_contains($searchable, $term)) {
                    continue 2;
                }
            }
            $found['total']++;
            if (count($found['acos']) < $limit) {
                $found['acos'][$id] = $path;
            }
        }

        return $found;
    }

    /**
     * Aliases from the root down to the node, joined with '/'
     *
     * @param int $id ACO id
     * @param array $nodes ACO rows keyed by id, with parent_id and alias
     * @return string
     */
    protected function _aliasPath(int $id, array $nodes): string
    {
        $aliases = [];
        $nodeId = $id;
        // the depth cap ends a parent_id cycle in a damaged tree
        for ($depth = 0; $depth < 32 && $nodeId !== null && isset($nodes[$nodeId]); $depth++) {
            array_unshift($aliases, $nodes[$nodeId]['alias']);
            $nodeId = $nodes[$nodeId]['parent_id'];
        }

        return implode('/', $aliases);
    }

    /**
     * Lower-cased text with backslashes read as slashes, for path matching
     *
     * @param string $text Path or search terms
     * @return string
     */
    protected function _searchable(string $text): string
    {
        return mb_strtolower(str_replace('\\', '/', $text));
    }
}
