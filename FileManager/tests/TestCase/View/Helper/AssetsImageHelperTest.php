<?php
declare(strict_types=1);

namespace Croogo\FileManager\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Croogo\Core\View\CroogoView;
use Croogo\FileManager\View\Helper\AssetsImageHelper;

/**
 * Admin thumbnails of assets whose file may not be on disk.
 */
class AssetsImageHelperTest extends TestCase
{
    protected AssetsImageHelper $AssetsImage;

    protected string $dir;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Earlier suites leave Configure without it, and the view's response needs a charset.
        Configure::write('App.encoding', 'UTF-8');
        $request = new ServerRequest(['url' => '/admin/file-manager/attachments']);
        // The theme carries the css-class map the image helper reads its thumbnail class from.
        $view = new CroogoView($request, null, null, ['theme' => 'Croogo/Core']);
        $this->AssetsImage = new AssetsImageHelper($view);

        $this->dir = 'assets_image_helper_test_' . uniqid();
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
     * The row whose file was deleted from disk used to make resize() return null,
     * which Html::link() refused with a TypeError - a 500 for the whole list.
     *
     * @return void
     */
    public function testThumbnailLinkOfMissingFileIsAMarkerNotAnError(): void
    {
        $path = '/' . $this->dir . '/gone.png';

        $html = $this->AssetsImage->thumbnailLink($path, 100, 200, ['adapter' => 'LocalAttachment']);

        $this->assertStringContainsString('asset-missing', $html);
        $this->assertStringContainsString('title="' . $path . '"', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    /**
     * A truncated or non-image upload is the same case: nothing to show, nothing to throw.
     *
     * @return void
     */
    public function testThumbnailLinkOfUnreadableImageIsAMarker(): void
    {
        $path = '/' . $this->dir . '/broken.png';
        file_put_contents(WWW_ROOT . ltrim($path, '/'), 'not an image');

        $html = $this->AssetsImage->thumbnailLink($path, 100, 200, ['adapter' => 'LocalAttachment']);

        $this->assertStringContainsString('asset-missing', $html);
    }

    /**
     * The normal case keeps the markup the templates built by hand before.
     *
     * @return void
     */
    public function testThumbnailLinkOfExistingImageLinksToTheOriginal(): void
    {
        $path = '/' . $this->dir . '/photo.png';
        $image = imagecreatetruecolor(40, 20);
        imagepng($image, WWW_ROOT . ltrim($path, '/'));

        $html = $this->AssetsImage->thumbnailLink(
            $path,
            100,
            200,
            ['adapter' => 'LocalAttachment'],
            ['alt' => 'Photo'],
            ['title' => 'Photo'],
        );

        $this->assertStringContainsString('<a href="' . $path . '"', $html);
        $this->assertStringContainsString('data-toggle="lightbox"', $html);
        $this->assertStringContainsString('title="Photo"', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringNotContainsString('asset-missing', $html);
    }
}
