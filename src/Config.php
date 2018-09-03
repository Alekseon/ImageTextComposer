<?php
/**
 * Copyright © Alekseon sp. z o.o.
 * http://www.alekseon.com/
 */
class Config
{
    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'default' => [
                'source_image_directory_path' => './img/',
                'result_image_width' => 1000,
                'result_image_height' => 1000,
                'result_image_can_crop' => false,
                'result_image_background_color' => '#ffffff',
                'text_letters_limit_in_line' => 10,
                'text_position_x' => 'right',  // 'left' / 'center' / 'right'
                'text_position_y' => 'center',  // 'top' / 'center' / 'bottom'
                'text_align' => 'left', // 'left' / 'center' / 'right'
                'text_offset_x' => 0,
                'text_offset_y' => 100,
                'text_max_width' => null,
                'text_max_height' => null,
                'text_lines_separator' => '|',
                'text_lines_limit' => 5,
                'text_line_spacing' => 100,
                'letters_image_directory_path' => './letters/',
                'letter_image_width' => null,
                'letter_image_height' => 100,
                'letter_space_width' => 100,
                'letter_spacing' => 0,
                'text_modificators' => ['tolower'],  // ['tolower', 'toupper']
                'letters_map' => [
                    'a' => 'a.png',
                    'b' => 'b.png',
                    'c' => 'c.png',
                    'd' => 'd.png',
                    'i' => 'i.png',
                    'm' => 'm.png',
                    'n' => 'n.png',
                    'r' => 'r.png',
                ]
            ],
            'testmode' => [
                'text_position_x' => 'left',
            ],
        ];
    }
}
