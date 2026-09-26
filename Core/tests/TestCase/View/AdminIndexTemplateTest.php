<?php
declare(strict_types=1);

namespace Croogo\Core\Test\TestCase\View;

use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Croogo\Core\TestSuite\TestCase;
use Croogo\Core\View\CroogoView;

/**
 * Common/admin_index as an app controller (no plugin) renders it.
 */
class AdminIndexTemplateTest extends TestCase
{
    /**
     * Before the fix the table headers went through __d(null, ...) and threw
     * a TypeError for any admin controller of the app itself.
     *
     * @return void
     */
    public function testRendersForAppControllerWithoutPlugin(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/things',
            'params' => ['prefix' => 'Admin', 'plugin' => null, 'controller' => 'Things', 'action' => 'index', 'pass' => []],
        ]);
        Router::createRouteBuilder('/')->prefix('Admin', function (RouteBuilder $routes): void {
            $routes->connect('/things/{action}/*', ['controller' => 'Things']);
        });
        Router::setRequest($request);
        $view = new CroogoView($request, null, null, ['name' => 'Things', 'theme' => 'Croogo/Core']);
        $view->set([
            'displayFields' => [
                'title' => ['label' => 'Title', 'sort' => false, 'type' => 'text', 'url' => [], 'options' => []],
            ],
            'things' => [new Entity(['id' => 1, 'title' => 'First thing'])],
            'searchFields' => [],
        ]);
        $view->setTemplatePath('Common');
        $view->setPlugin('Croogo/Core');
        $view->disableAutoLayout();

        $html = $view->render('admin_index');

        $this->assertStringContainsString('<th>Title</th>', $html);
        $this->assertStringContainsString('First thing', $html);
    }
}
