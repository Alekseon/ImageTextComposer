<?php
/**
 * Copyright © Alekseon sp. z o.o.
 * http://www.alekseon.com/
 */

require_once 'TextModificators.php';
require_once 'Config.php';

/**
 * Class ImageTextComposer
 */
class ImageTextComposer
{
    const TRANSPARENT_COLOR = 'transparent';

    const TEXT_POSITION_TOP = 'top';
    const TEXT_POSITION_BOTTOM = 'bottom';
    const TEXT_POSITION_LEFT = 'left';
    const TEXT_POSITION_RIGHT = 'right';
    const TEXT_POSITION_CENTER = 'center';

    const TEXT_ALIGN_LEFT = 'left';
    const TEXT_ALIGN_RIGHT = 'right';
    const TEXT_ALIGN_CENTER = 'center';

    /**
     * @var
     */
    private $resultImage;
    /**
     * @var
     */
    private $resultImageType;
    /**
     * @var array
     */
    private $config;
    /**
     * @var array
     */
    private $params;
    /**
     * @var Config|null|TextModificators
     */
    private $textModificatorsObject;

    /**
     * ImageTextComposer constructor.
     * @param array $params
     * @param null $configObject
     * @param null $textModificatorsObject
     */
    public function __construct($params = [], $configObject = null, $textModificatorsObject = null)
    {
        if (!$textModificatorsObject) {
            $textModificatorsObject = new Config();
        }
        $this->config = $textModificatorsObject->getConfig();
        $this->params = $params;
        if (!$textModificatorsObject) {
            $textModificatorsObject = new TextModificators();
        }
        $this->textModificatorsObject = $textModificatorsObject;
    }

    /**
     * @param $configField
     * @param bool $obligatory
     * @param null $default
     * @return mixed
     * @throws Exception
     */
    private function getConfig($configField, $obligatory = true, $default = null)
    {
        $modes = explode('/', $this->getParam('mode', []));

        foreach($modes as $mode) {
            if ($mode && isset($this->config[$mode])) {
                if (array_key_exists($configField, $this->config[$mode])) {
                    return $this->config[$mode][$configField];
                }
            }
        }

        if (array_key_exists($configField, $this->config['default'])) {
            return $this->config['default'][$configField];
        }

        if ($obligatory) {
            throw new Exception('Invalid Configuration.');
        }

        return $default;
    }

    /**
     * @param $imagePath
     * @return resource
     * @throws Exception
     */
    private function getImageSource($imagePath, $setResultImageType = false)
    {
        if (!file_exists($imagePath) || !is_file($imagePath)) {
            throw new Exception('Image does not exist.');
        }

        $imageInfo = getimagesize($imagePath);

        if ($setResultImageType) {
            $this->resultImageType = $imageInfo[2];
        }

        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $srcImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_GIF:
                $srcImage = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($imagePath);
                break;
            default:
                throw new Exception('Image format is not supported.');
        }

        return $srcImage;
    }

    /**
     * @throws Exception
     */
    private function initResultImage()
    {
        $sourceImage = $this->getParam('image');
        $imagePath = $this->getConfig('source_image_directory_path') . $sourceImage;

        $this->resultImage = $this->getImageSource($imagePath, true);

        $this->resize(
            $this->resultImage,
            $this->getConfig('result_image_width', false, false),
            $this->getConfig('result_image_height', false, false),
            $this->getConfig('result_image_can_crop', false, true),
            $this->getConfig('result_image_background_color', false, false)
        );

        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @param $color
     * @return resource
     */
    private function getNewImage($width, $height, $color = '#000000')
    {
        $image = imagecreatetruecolor($width,  $height);

        if ($color == self::TRANSPARENT_COLOR) {
            imagesavealpha($image, true);
            imagealphablending($image, false);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
        } else if ($color) {
            list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
            $bgColor = imagecolorallocate($image, $r, $g, $b);
            imagefill($image, 0, 0, $bgColor);
        }

        return $image;
    }

    /**
     * @param $param
     * @param $default
     * @return mixed
     */
    private function getParam($param, $default = null)
    {
        if (array_key_exists($param, $this->params)) {
            return $this->params[$param];
        }

        return $default;
    }

    /**
     * @param $image
     * @param bool $width
     * @param bool $height
     * @param bool $canCropImage
     * @param string $backgroundColor
     * @return $this
     */
    private function resize(&$image, $width = false, $height = false, $canCropImage = true, $backgroundColor = self::TRANSPARENT_COLOR)
    {
        if (!$width && !$height) {
            return $this;
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        if (!$width && $height) {
            $width = $origWidth * ($height / $origHeight);
        } elseif (!$height && $width) {
            $height = $origHeight* ($width / $origWidth);
        }

        $newWidth = $width;
        $newHeight = $height;

        $origProportions = $origWidth / $origHeight;
        $proportions = $width / $height;

        if ($origProportions > $proportions) {
            $height = $height / ($origProportions / $proportions);
        }
        if ($origProportions < $proportions) {
            $width = $width / ($proportions / $origProportions);
        }

        if ($canCropImage) {
            $newWidth = $width;
            $newHeight = $height;
        }

        $resizedImage = $this->getNewImage($newWidth,  $newHeight, $backgroundColor);

        $dstX = ($newWidth - $width) / 2;
        $dstY = ($newHeight - $height) / 2;
        imagecopyresampled($resizedImage, $image, $dstX, $dstY, 0, 0, $width, $height, $origWidth, $origHeight);
        $image = $resizedImage;
        return $this;
    }

    /**
     * @param $letter
     * @return resource
     * @throws Exception
     */
    private function getLetterImage($letter)
    {
        $letterMap = $this->getConfig('letters_map');
        if (isset($letterMap[$letter])) {
            $imagePath = $this->getConfig('letters_image_directory_path') . $letterMap[$letter];
            $letterImage = $this->getImageSource($imagePath);
            $this->resize(
                $letterImage,
                $this->getConfig('letter_image_width', false, false),
                $this->getConfig('letter_image_height', false, false),
                true,
                self::TRANSPARENT_COLOR
            );
            return $letterImage;
        } else {
            throw new Exception('Incorrect Letter "' . $letter . '');
        }
    }

    /**
     * @param $textImage
     * @return float|int
     * @throws Exception
     */
    private function getTextDstX($textImage)
    {
        $textPositionX = $this->getConfig('text_position_x', false, self::TEXT_POSITION_LEFT);
        $textOffsetX = (int)$this->getConfig('text_offset_x', false, 0);
        switch ($textPositionX) {
            case self::TEXT_POSITION_CENTER:
                $dstX = (imagesx($this->resultImage) / 2) + $textOffsetX - (imagesx($textImage) / 2);
                break;
            case self::TEXT_POSITION_RIGHT:
                $dstX = imagesx($this->resultImage) - imagesx($textImage) - $textOffsetX;
                break;
            case self::TEXT_POSITION_LEFT:
            default:
                $dstX = $textOffsetX;
        }
        return $dstX;
    }

    /**
     * @param $textImage
     * @return float|int
     * @throws Exception
     */
    private function getTextDstY($textImage)
    {
        $textPositionY = $this->getConfig('text_position_y', false, self::TEXT_POSITION_TOP);
        $textOffsetY = (int)$this->getConfig('text_offset_y', false, 0);
        switch ($textPositionY) {
            case self::TEXT_POSITION_CENTER:
                $dstY = (imagesy($this->resultImage) / 2) + $textOffsetY - (imagesy($textImage) / 2);
                break;
            case self::TEXT_POSITION_BOTTOM:
                $dstY = imagesy($this->resultImage) - imagesy($textImage) - $textOffsetY;
                break;
            case self::TEXT_POSITION_TOP:
            default:
                $dstY = $textOffsetY;
        }
        return $dstY;
    }

    /**
     * @param $letter
     * @return array|bool
     * @throws Exception
     */
    private function getLetterData($letter)
    {
        /** space letter */
        $letterSpaceWidth = (int)$this->getConfig('letter_space_width', false, 0);
        if ($letter == ' ' && $letterSpaceWidth) {
            return [
                'image' => false,
                'width' => $letterSpaceWidth,
                'height' => 0,
            ];
        }

        try {
            $letterImage = $this->getLetterImage($letter);
            $letterWidth = imagesx($letterImage);
            $letterHeight = imagesy($letterImage);
            return [
                'image' => $letterImage,
                'width' => $letterWidth,
                'height' => $letterHeight,
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $text
     * @return mixed
     * @throws Exception
     */
    public function applyTextModificators(&$text)
    {
        $textModificator = $this->textModificatorsObject;
        $modificators = $this->getConfig('text_modificators', false, []);
        foreach($modificators as $modificator) {

            if (method_exists($textModificator, $modificator)) {
                $text = $textModificator->$modificator($text);
            }
        }
        return $text;
    }

    /**
     * @throws Exception
     */
    private function addText()
    {
        $text = $this->getParam('text');
        $this->applyTextModificators($text);
        $letters = str_split($text);
        $letterSpacing = $this->getConfig('letter_spacing', false, 0);
        $lineSpacing = $this->getConfig('text_line_spacing', false, 0);
        $textAlign = $this->getConfig('text_align', false, 0);
        $textLinesLimit = (int)$this->getConfig('text_lines_limit', false, false);
        $lettersLimitInLine = (int)$this->getConfig('text_letters_limit_in_line', false, false);
        $textMaxWidth = (int)$this->getConfig('text_max_width', false, false);
        $textMaxHeight = (int)$this->getConfig('text_max_height', false, false);
        $linesSeparator = $this->getConfig('text_lines_separator', false, false);;

        $lineNumbers = 0;
        $textData = [];

        foreach($letters as $letter) {

            if ($linesSeparator && $letter == $linesSeparator) {
                $lineNumbers++;
                continue;
            }

            if ($textLinesLimit && $lineNumbers >= $textLinesLimit) {
                break;
            }


            $letterData = $this->getLetterData($letter);
            if (!$letterData) {
                continue;
            }

            if (!isset($textData[$lineNumbers])) {
                $textData[$lineNumbers] = [
                    'letters' => [],
                    'width' => 0,
                    'height' => 0,
                ];
            }

            $lettersAmountInLine = count($textData[$lineNumbers]['letters']);

            if ($lettersLimitInLine && $lettersAmountInLine < $lettersLimitInLine) {
                $textLineWidth = $textData[$lineNumbers]['width'] + $letterData['width'] + ($lettersAmountInLine > 0 ? $letterSpacing : 0);
                if (!$textMaxWidth || $textLineWidth <= $textMaxWidth) {
                    $textData[$lineNumbers]['width'] = $textLineWidth;
                    $textData[$lineNumbers]['letters'][] = $letterData;
                    $textData[$lineNumbers]['height'] = max($textData[$lineNumbers]['height'], $letterData['height']);
                }
            }
        }

        $textWidth = 0;
        $textHeight = 0;

        foreach($textData as $lineNumber => $lineData) {
            $textWidth = max($textWidth, $lineData['width']);
            $lineHeight = $textHeight > 0 ? $lineSpacing : 0;
            $lineHeight += $lineData['height'];
            if (!$textMaxHeight || ($textHeight + $lineHeight <= $textMaxHeight)) {
                $textHeight += $lineHeight;
            } else {
                $lineNumbers = min($lineNumbers, $lineNumber);
                break;
            }
        }

        if (!$textWidth || !$textHeight) {
            return $this;
        }

        $textImage = $this->getNewImage($textWidth, $textHeight, self::TRANSPARENT_COLOR);
        $dstY = 0;
        foreach($textData as $lineNumber => $lineData) {

            if ($lineNumber > $lineNumbers) {
                break;
            }

            switch ($textAlign) {
                case self::TEXT_ALIGN_RIGHT:
                    $dstX = $textWidth - $lineData['width'];
                    break;
                case self::TEXT_ALIGN_CENTER:
                    $dstX = ($textWidth / 2) - ($lineData['width'] / 2);
                    break;
                case self::TEXT_ALIGN_LEFT:
                default:
                    $dstX = 0;
            }

            foreach($lineData['letters'] as $letterData) {
                if ($letterData['image']) {
                    imagecopyresampled(
                        $textImage,
                        $letterData['image'],
                        $dstX,
                        $dstY + (($lineData['height'] - $letterData['height']) / 2),
                        0,
                        0,
                        $letterData['width'],
                        $letterData['height'],
                        $letterData['width'],
                        $letterData['height']
                    );
                }
                $dstX += $letterData['width'] + $letterSpacing;
            }
            $dstY += $lineData['height'] + $lineSpacing;
        }

        imagecopyresampled(
            $this->resultImage,
            $textImage,
            $this->getTextDstX($textImage),
            $this->getTextDstY($textImage),
            0,
            0,
            imagesx($textImage),
            imagesy($textImage),
            imagesx($textImage),
            imagesy($textImage)
        );
        return $this;
    }

    /**
     * @return resource
     * @throws Exception
     */
    public function getResultImage()
    {
        $this->initResultImage();
        $this->addText();

        return $this->resultImage;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $resultImage = $this->getResultImage();
        switch ($this->resultImageType) {
            case IMAGETYPE_GIF:
                header("Content-Type: image/gif");
                imagegif($resultImage);
                break;
            case IMAGETYPE_PNG:
                header("Content-Type: image/png");
                imagepng($resultImage);
                break;
            case IMAGETYPE_JPEG:
            default:
                header("Content-Type: image/jpeg");
                imagejpeg($resultImage);
                break;
        }
        imagedestroy($resultImage);
    }
}
