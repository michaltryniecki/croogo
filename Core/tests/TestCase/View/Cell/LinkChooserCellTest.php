<?php
declare(strict_types=1);

namespace Croogo\Core\Test\TestCase\View\Cell;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Croogo\Core\TestSuite\TestCase;
use Croogo\Core\View\CroogoView;

/**
 * The link chooser dropdown next to a link field (Menus links, `link` settings).
 */
class LinkChooserCellTest extends TestCase
{
    /**
     * @var mixed
     */
    protected $linkChoosers;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->linkChoosers = Configure::read('Croogo.linkChoosers');
        Configure::write('Croogo.linkChoosers', [
            'Images' => [
                'title' => 'Images',
                'description' => 'Pick an image',
                'url' => '/admin/file-manager/attachments?chooser=1',
            ],
        ]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        Configure::write('Croogo.linkChoosers', $this->linkChoosers);

        parent::tearDown();
    }

    /**
     * Bootstrap 5 finds the modal to open through `data-bs-target` only. The link
     * used to carry just `data-target`: the click threw in Bootstrap's data-api and
     * the chooser list loaded into a modal that never showed. `data-target` stays,
     * core/choose.js reads it.
     *
     * @return void
     */
    public function testChooserLinkOpensTheModalUnderBootstrap5(): void
    {
        $request = new ServerRequest(['url' => '/admin/menus/links/add']);
        Router::setRequest($request);
        $view = new CroogoView($request, null, null, ['theme' => 'Croogo/Core']);

        $html = (string)$view->cell('Croogo/Core.Admin/LinkChooser', ['#link-link']);

        $this->assertMatchesRegularExpression('/<a [^>]*class="dropdown-item link-chooser"/', $html);
        $this->assertStringContainsString('data-bs-toggle="modal"', $html);
        $this->assertStringContainsString('data-bs-target="#link-chooser"', $html);
        $this->assertStringContainsString('data-target="#link-chooser"', $html);
        $this->assertStringContainsString('data-chooser-target="#link-link"', $html);
    }
}
