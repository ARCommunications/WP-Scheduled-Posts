<?php

namespace WPSP;

class Helper
{
    public static function get_all_post_type()
    {
        $postType = get_post_types('', 'names');
        $not_neccessary_post_types = array('custom_css', 'attachment', 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'user_request', 'product_variation', 'shop_order', 'scheduled-action', 'shop_order_refund', 'shop_coupon', 'nxs_qp');
        return array_diff($postType, $not_neccessary_post_types);
    }

    public static function get_all_category()
    {
        $category  = get_categories(array(
            'orderby' => 'name',
            'order'   => 'ASC',
            "hide_empty" => 0,
        ));
        $category = wp_list_pluck($category, 'name', 'slug');
        return array_merge(array('all' => 'All Categories'), $category);
    }

    public static function get_all_roles_as_dropdown($selected = array(), $skip_subscribe = false)
    {
        $p = '';
        $r = '';
        $editable_roles = \get_editable_roles();
        if (is_array($editable_roles) && count($editable_roles) > 0) {
            foreach ($editable_roles as $role => $details) {
                if ($role == 'subscriber' && $skip_subscribe == true) {
                    continue;
                }
                $name = translate_user_role($details['name']);
                if ($selected !== "" && is_array($selected) && in_array($role, $selected)) {
                    $p .= "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
                } else {
                    $r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
                }
            }
        }

        return $p . $r;
    }

    public static function get_all_roles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $allroles = wp_list_pluck(\get_editable_roles(), 'name');
        unset($allroles['subscriber']);
        return $allroles;
    }

    public static function is_user_allow()
    {
        global $current_user;
        $allow_user_by_role = \WPSP\Helper::get_settings('allow_user_by_role');
        $allow_user_by_role = (!is_array($allow_user_by_role) && count($allow_user_by_role) == 0) ? array('administrator') : $allow_user_by_role;
        if (!is_array($current_user->roles)) return false;
        foreach ($current_user->roles as $ur) {
            if (in_array($ur, $allow_user_by_role)) {
                return true;
                break;
            }
        }
        return false;
    }

    public static function get_settings($key)
    {
        global $wpsp_settings;
        if (isset($wpsp_settings->{$key})) {
            return $wpsp_settings->{$key};
        }
        return;
    }

    /**
     * Check Supported Post type for admin page and plugin main settings page
     * 
     * @return bool
     * @version 3.1.12
     */

    public static function plugin_page_hook_suffix($current_post_type, $hook)
    {
        $allow_post_types = (!empty(self::get_settings('allow_post_types')) ? array('post') : self::get_settings('allow_post_types'));
        if (
            in_array($current_post_type, $allow_post_types) ||
            $hook == 'posts_page_' . WPSP_SETTINGS_SLUG . '-post' ||
            $hook == 'toplevel_page_' .  WPSP_SETTINGS_SLUG ||
            $hook == 'scheduled-posts_page_' . WPSP_SETTINGS_SLUG
        ) {
            return true;
        }
        return false;
    }

    /**
     * Email Notify review Email List
     * @return array
     */
    public static function email_notify_review_email_list()
    {
        global $wpdb;
        $email = array();
        // collect email from role
        $roles = get_option('wpscp_notify_author_role_sent_review');
        if (!empty($roles)) {
            $email = wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'role__in'    => $roles
            )), 'user_email');
        }
        // collect email from email fields
        $meta_email = array_values(get_option('wpscp_notify_author_email_sent_review'));
        if (!empty($meta_email)) {
            $email = array_merge($email, $meta_email);
        }
        // get email from username
        $meta_username = get_option('wpscp_notify_author_username_sent_review');
        if (!empty($meta_username)) {
            $email = array_merge($email, wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'login__in'    => $meta_username
            )), 'user_email'));
        }
        return array_unique($email);
    }

    public static function email_notify_schedule_email_list()
    {
        global $wpdb;
        $email = array();
        // collect email from role
        $roles = get_option('wpscp_notify_author_post_schedule_role');
        if (!empty($roles)) {
            $email = wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'role__in'    => $roles
            )), 'user_email');
        }
        // collect email from email fields
        $meta_email = array_values(get_option('wpscp_notify_author_post_schedule_email'));
        if (!empty($meta_email)) {
            $email = array_merge($email, $meta_email);
        }
        // get email from username
        $meta_username = get_option('wpscp_notify_author_post_schedule_username');
        if (!empty($meta_username)) {
            $email = array_merge($email, wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'login__in'    => $meta_username
            )), 'user_email'));
        }
        return array_unique($email);
    }

    /**
     * social single profile data return
     * wpscp_get_social_profile
     *
     * @param  mixed $profile
     * @return array
     * @since 3.3.0
     */

    public static function get_social_profile($profile)
    {
        $profile = get_option($profile);
        $is_pro_wpscp = apply_filters('wpsp_social_profile_limit_checkpoint', $profile);
        if (class_exists('WpScp_Pro') && $is_pro_wpscp === true) {
            return $profile;
        }
        $new_profile = $profile[0];
        return [$new_profile];
    }
}
