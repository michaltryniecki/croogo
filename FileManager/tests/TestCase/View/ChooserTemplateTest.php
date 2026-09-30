<?php
declare(strict_types=1);

namespace Croogo\FileManager\Test\TestCase\View;

use ArrayIterator;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Croogo\Core\View\CroogoView;

/**
 * The attachment chooser as the popup of a product form loads it.
 */
class ChooserTemplateTest extends TestCase
{
    protected string $dir;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.encoding', 'UTF-8');
        $this->dir = 'chooser_template_test_' . uniqid();
        mkdir(WWW_ROOT . $this->dir, 0777, true);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        foreach (glob(WWW_ROOT . $this->dir . '/*') as $file) {
            unlink($file);
        }
        rmdir(WWW_ROOT . $this->dir);

        parent::tearDown();
    }

    /**
     * @param int $id Attachment and asset id
     * @param string $filename File name inside the test directory
     * @param \Cake\I18n\DateTime $created Upload time
     * @return \Cake\ORM\Entity
     */
    protected function attachment(int $id, string $filename, DateTime $created): Entity
    {
        return new Entity([
            'id' => $id,
            'title' => $filename,
            'asset' => new Entity([
                'id' => $id,
                'filename' => $filename,
                'path' => '/' . $this->dir . '/' . $filename,
                'mime_type' => 'image/png',
                'adapter' => 'LocalAttachment',
                'created' => $created,
            ]),
        ]);
    }

    /**
     * @param list<\Cake\ORM\Entity> $attachments Rows of the page
     * @param int $total Rows in the whole result
     * @return string Rendered chooser
     */
    protected function renderChooser(array $attachments, int $total): string
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/attachments',
            'params' => [
                'prefix' => 'Admin',
                'plugin' => 'Croogo/FileManager',
                'controller' => 'Attachments',
                'action' => 'index',
                'pass' => [],
            ],
            'query' => ['chooser' => '1', 'chooser_type' => 'image'],
        ]);
        Router::createRouteBuilder('/')->prefix('Admin', function (RouteBuilder $routes): void {
            $routes->plugin('Croogo/FileManager', ['path' => '/file-manager'], function (RouteBuilder $routes): void {
                $routes->connect('/attachments', ['controller' => 'Attachments', 'action' => 'index']);
                $routes->connect('/attachments/{action}/*', ['controller' => 'Attachments']);
            });
        });
        Router::setRequest($request);

        $view = new CroogoView($request, null, null, [
            'name' => 'Attachments',
            'theme' => 'Croogo/Core',
            'plugin' => 'Croogo/FileManager',
        ]);
        $view->loadHelper('Croogo/FileManager.AssetsImage');
        $view->loadHelper('Time');
        $view->set('attachments', new PaginatedResultSet(new ArrayIterator($attachments), [
            'alias' => 'Attachments',
            'count' => count($attachments),
            'totalCount' => $total,
            'perPage' => 24,
            'pageCount' => (int)ceil($total / 24),
            'currentPage' => 1,
            'start' => 1,
            'end' => count($attachments),
            'hasPrevPage' => false,
            'hasNextPage' => $total > 24,
            'sort' => null,
            'direction' => null,
            'sortDefault' => false,
            'directionDefault' => false,
            'completeSort' => [],
            'limit' => null,
            'scope' => null,
            'finder' => 'all',
        ]));
        $view->setTemplatePath('Admin/Attachments');
        $view->disableAutoLayout();

        return $view->render('chooser');
    }

    /**
     * The card shows the cached thumbnail and only links to the original; callers keep
     * finding an `a.item-choose` carrying the asset path.
     *
     * @return void
     */
    public function testCardShowsAThumbnailAndLinksToTheOriginal(): void
    {
        imagepng(imagecreatetruecolor(800, 600), WWW_ROOT . $this->dir . '/photo.png');

        $html = $this->renderChooser([$this->attachment(1, 'photo.png', new DateTime('-2 days'))], 1);

        $original = '/' . $this->dir . '/photo.png';
        $this->assertStringContainsString('src="/' . $this->dir . '/photo.resized-200x150.png"', $html);
        $this->assertStringNotContainsString('src="' . $original . '"', $html);
        $this->assertMatchesRegularExpression(
            '#<a href="' . preg_quote($original, '#') . '"[^>]*class="item-choose#',
            $html,
        );
        $this->assertStringNotContainsString('asset-missing', $html);
    }

    /**
     * @return void
     */
    public function testFileMissingOnDiskIsShownButCannotBeChosen(): void
    {
        $html = $this->renderChooser([$this->attachment(1, 'gone.png', new DateTime('-2 days'))], 1);

        $this->assertStringContainsString('asset-missing', $html);
        $this->assertStringContainsString('gone.png', $html);
        $this->assertStringNotContainsString('item-choose', $html);
    }

    /**
     * @return void
     */
    public function testJustUploadedFileIsMarkedNew(): void
    {
        imagepng(imagecreatetruecolor(40, 30), WWW_ROOT . $this->dir . '/fresh.png');
        imagepng(imagecreatetruecolor(40, 30), WWW_ROOT . $this->dir . '/old.png');

        $fresh = $this->renderChooser([$this->attachment(1, 'fresh.png', new DateTime('-1 minute'))], 1);
        $old = $this->renderChooser([$this->attachment(2, 'old.png', new DateTime('-1 hour'))], 1);

        $this->assertStringContainsString('bg-green-lt', $fresh);
        $this->assertStringNotContainsString('bg-green-lt', $old);
    }

    /**
     * With more than one page the pager is also above the grid, where it is seen
     * without scrolling past two dozen thumbnails.
     *
     * @return void
     */
    public function testPagerIsAlsoAboveTheGridWhenThereAreMorePages(): void
    {
        imagepng(imagecreatetruecolor(40, 30), WWW_ROOT . $this->dir . '/photo.png');
        $row = [$this->attachment(1, 'photo.png', new DateTime('-2 days'))];

        $this->assertSame(1, substr_count($this->renderChooser($row, 1), 'class="pagination'));
        $this->assertSame(2, substr_count($this->renderChooser($row, 60), 'class="pagination'));
    }
}
