<?php
/**
 * Plugin Name:       Elementor Carousel with Custom Links
 * Description:       Add a custom link field to images and automatically apply it in Elementor Image Carousels, disabling lightbox.
 * Version:           1.0
 * Author:            Surflibre
 */

if (!defined('ABSPATH')) exit;

class Elementor_Carousel_Custom_Links {

    public function __construct() {
        // Ajouter champs custom dans la médiathèque
        add_filter('attachment_fields_to_edit', [$this, 'attachment_fields_to_edit'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'attachment_fields_to_save'], 10, 2);

        // Intervenir sur le rendu du carousel
        add_filter('elementor/widget/render_content', [$this, 'override_carousel_links'], 10, 2);
    }

    /**
     * Champs custom dans la médiathèque
     */
    public function attachment_fields_to_edit($form_fields, $post) {
        $form_fields['elementor_carousel_custom_link'] = [
            'label' => __('Custom Link', 'elementor'),
            'input' => 'text',
            'value' => get_post_meta($post->ID, 'elementor_carousel_custom_link', true),
            'helps' => __('Add a custom link that will be applied automatically in Elementor Image Carousels.', 'elementor'),
        ];

        $target = get_post_meta($post->ID, 'elementor_carousel_custom_link_target', true);
        $checked = ($target === '1') ? 'checked' : '';
        $form_fields['elementor_carousel_custom_link_target'] = [
            'label' => __('Open in new tab?', 'elementor'),
            'input' => 'html',
            'html'  => "<input type='checkbox' $checked name='attachments[{$post->ID}][elementor_carousel_custom_link_target]' />",
            'value' => $target,
            'helps' => __('Open link in new tab if checked.', 'elementor'),
        ];

        return $form_fields;
    }

    /**
     * Sauvegarde des champs custom
     */
    public function attachment_fields_to_save($post, $attachment) {
        $link = $attachment['elementor_carousel_custom_link'] ?? '';
        update_post_meta($post['ID'], 'elementor_carousel_custom_link', esc_url_raw($link));

        $target = isset($attachment['elementor_carousel_custom_link_target']) && $attachment['elementor_carousel_custom_link_target'] === 'on' ? '1' : '0';
        update_post_meta($post['ID'], 'elementor_carousel_custom_link_target', $target);

        return $post;
    }

    /**
     * Override du carousel Elementor pour appliquer le lien
     */
    public function override_carousel_links($content, $widget) {
        if ($widget->get_name() !== 'image-carousel') return $content;

        $settings = $widget->get_settings_for_display();
        if (empty($settings['carousel'])) return $content;

        foreach ($settings['carousel'] as $attachment) {
            $custom_link = get_post_meta($attachment['id'], 'elementor_carousel_custom_link', true);
            $target = get_post_meta($attachment['id'], 'elementor_carousel_custom_link_target', true) === '1' ? '_blank' : '';

            if ($custom_link && !empty($attachment['url'])) {
                // Remplace le href de l'image dans le HTML
                $pattern = '/<a([^>]*?)href=["\']' . preg_quote($attachment['url'], '/') . '["\']([^>]*)>/i';
                $replacement = '<a$1 href="' . esc_url($custom_link) . '" target="' . esc_attr($target) . '" data-elementor-open-lightbox="no"$2>';
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }
}

// Initialisation
new Elementor_Carousel_Custom_Links();
