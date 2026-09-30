<?php

namespace Croogo\FileManager\View\Helper;

use Cake\Utility\Hash;
use Croogo\Core\Croogo;
use Croogo\Core\View\Helper\ImageHelper;

class AssetsImageHelper extends ImageHelper
{

    public function resize($path, $width, $height, $options = [], $htmlAttributes = [], $return = false)
    {
        $filename = basename($path);
        $uploadsDir = dirname(basename($path));
        if ($uploadsDir === '.') {
            $uploadsDir = '';
        }
        $cacheDir = dirname($path);
        $options = Hash::merge([
            'aspect' => true,
            'adapter' => false,
            'cacheDir' => $cacheDir,
            'uploadsDir' => $uploadsDir,
        ], $options);
        $adapter = $options['adapter'];
        $aspect = $options['aspect'];
        if ($adapter === 'LegacyLocalAttachment') {
            $options['cacheDir'] = 'resized';
            $options['resizedInd'] = '.resized-';
            $options['uploadsDir'] = 'uploads';
        }
        $result = parent::resize($path, $width, $height, $options, $htmlAttributes, $return);
        $record = compact('result', 'path', 'width', 'height', 'aspect', 'htmlAttributes', 'adapter');
        Croogo::dispatchEvent('Assets.AssetsImageHelper.resize', $this->_View, compact('record'));

        return $result;
    }

    /**
     * A thumbnail linking to the full image, or a "file missing" marker.
     *
     * resize() returns null when the source file is not on disk or is not a readable image.
     * The admin templates passed that straight to Html::link(), whose title is typed
     * array|string, so ONE asset row without a file turned the whole attachment list into a
     * 500 page. Every admin thumbnail goes through here so a missing file costs one cell.
     *
     * @param string $path Asset path, relative to the webroot
     * @param int $width Maximum thumbnail width
     * @param int $height Maximum thumbnail height
     * @param array $options Options for resize()
     * @param array $htmlAttributes Attributes of the <img> tag
     * @param array $linkOptions Attributes of the link around it
     * @return string
     */
    public function thumbnailLink(
        string $path,
        int $width,
        int $height,
        array $options = [],
        array $htmlAttributes = [],
        array $linkOptions = [],
    ): string {
        $img = $this->resize($path, $width, $height, $options, $htmlAttributes);
        if (!is_string($img) || $img === '') {
            return $this->missing($path);
        }

        return $this->Html->link($img, $path, $linkOptions + [
            'escape' => false,
            'data-toggle' => 'lightbox',
        ]);
    }

    /**
     * Marker shown in place of an image whose file is not on disk.
     *
     * @param string $path Asset path, shown as the tooltip
     * @return string
     */
    public function missing(string $path): string
    {
        return $this->Html->tag('span', __d('croogo', 'File missing on disk'), [
            'class' => 'badge bg-danger-lt asset-missing',
            'title' => $path,
        ]);
    }

    /**
     * Looks upon $data and extract FeaturedImage tag or value
     *
     * By default, this method will return the generated <img> tag.  Pass
     * array('tag' => false) in the $options array to get the value.
     *
     * If you have multiple versions of image, you can retrieve a specific image
     * by passing an integer value in the `maxWidth` key.
     *
     * Example:
     *
     *  echo $this->AssetsImage->featured($node, array(
     *      'class' => 'gallery featured-image',
     *      'tag' => true,
     *      'maxWidth' => 500,
     *  ));
     *
     * @param array $data Array of record containing `LinkedAssets` key
     * @param array $options Array of options
     */
    public function featured($data, $options = [])
    {
        if (empty($data['LinkedAssets']['FeaturedImage'])) {
            return null;
        }
        $options = Hash::merge([
            'class' => 'featured-image',
            'tag' => true,
        ], $options);
        $tag = $options['tag'];
        $image = $data['LinkedAssets']['FeaturedImage'];
        $path = $image['path'];
        if (isset($options['maxWidth'])) {
            $maxWidth = $options['maxWidth'];
            unset($options['maxWidth']);
            if ($image['width'] > $maxWidth && !empty($image['Versions'])) {
                $found = false;
                foreach ($image['Versions'] as $version) {
                    $smallest = $version['path'];
                    if ($version['width'] <= $maxWidth) {
                        $path = $version['path'];
                        $found = true;
                        break;
                    }
                }
                if (!$found && isset($smallest)) {
                    $path = $smallest;
                }
            }
        }
        if ($tag) {
            return $this->Html->image($path, $options);
        } else {
            return $path;
        }
    }
}
