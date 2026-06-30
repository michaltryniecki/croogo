<?php

namespace Croogo\Core\Action\Admin;

use Crud\Action\IndexAction as CrudIndexAction;

class IndexAction extends CrudIndexAction
{
    protected function _handle(): ?\Cake\Http\Response
    {
        parent::_handle();

        $this->_controller()->set([
            'displayFields' => $this->getConfig('displayFields'),
            'searchFields' => $this->getConfig('searchFields'),
        ]);
    }
}
