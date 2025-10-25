<?php
/**
 * Snippet: Adds an "Ingresar/Registrarse" menu item with account dropdown when logged in.
 *
 * Copy this code into your theme's functions.php or a Code Snippets plugin.
 */

add_action( 'init', 'azimut_register_auth_menu_shortcode' );
add_filter( 'wp_nav_menu_items', 'azimut_add_auth_menu_item', 10, 2 );

/**
 * Registers the shortcode so the component can be added inside block builders.
 */
function azimut_register_auth_menu_shortcode() {
    add_shortcode( 'azimut_auth_menu', 'azimut_render_auth_menu_shortcode' );
}

/**
 * Keeps track of whether the component rendered, so assets can be enqueued lazily.
 */
function azimut_mark_auth_menu_rendered() {
    $GLOBALS['azimut_auth_menu_rendered'] = true;
}

/**
 * Returns whether the component rendered on the current page load.
 *
 * @return bool
 */
function azimut_has_auth_menu_rendered() {
    return ! empty( $GLOBALS['azimut_auth_menu_rendered'] );
}

/**
 * Appends the authentication menu item to the primary navigation menu.
 *
 * @param string   $items The HTML list content for the menu items.
 * @param stdClass $args  An object containing wp_nav_menu() arguments.
 *
 * @return string
 */
function azimut_add_auth_menu_item( $items, $args ) {
    // Change this to match the `theme_location` of your header menu if needed.
    $target_theme_location = 'primary';

    if ( ! isset( $args->theme_location ) || $target_theme_location !== $args->theme_location ) {
        return $items;
    }

    $items .= azimut_get_auth_menu_markup( 'menu' );

    return $items;
}

/**
 * Shortcode callback for [azimut_auth_menu].
 *
 * @return string
 */
function azimut_render_auth_menu_shortcode() {
    return azimut_get_auth_menu_markup( 'shortcode' );
}

/**
 * Generates the HTML markup for the auth menu.
 *
 * @param string $context Context of the render (menu|shortcode).
 *
 * @return string
 */
function azimut_get_auth_menu_markup( $context = 'menu' ) {
    azimut_mark_auth_menu_rendered();

    $is_shortcode   = ( 'shortcode' === $context );
    $wrapper_tag    = $is_shortcode ? 'div' : 'li';
    $wrapper_classes = array( 'auth-menu', 'menu-item-auth' );

    if ( ! $is_shortcode ) {
        $wrapper_classes[] = 'menu-item';
        $wrapper_classes[] = 'menu-item-type-custom';
    } else {
        $wrapper_classes[] = 'auth-menu--standalone';
    }

    $child_item_class = $is_shortcode ? 'auth-menu__item' : 'auth-menu__item menu-item';

    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $display_name = $current_user->display_name ?: $current_user->user_nicename;
        // 🔁 Reemplaza $account_url con la URL exacta de tu página "Mi cuenta" si es distinta.
        $account_url  = function_exists( 'wc_get_page_permalink' )
            ? wc_get_page_permalink( 'myaccount' )
            : admin_url( 'profile.php' );
        // 🔁 Reemplaza $logout_url con el enlace de cierre de sesión que uses en tu sitio.
        $logout_url   = wp_logout_url( home_url() );

        $wrapper_classes[] = 'menu-item-has-children';

        $markup = sprintf(
            '<%1$s class="%2$s">
                <button type="button" class="auth-menu__toggle" aria-haspopup="true" aria-expanded="false">
                    <span class="auth-menu__label">%3$s</span>
                    <span class="auth-menu__icon" aria-hidden="true">▾</span>
                </button>
                <ul class="sub-menu auth-menu__dropdown" hidden>
                    <li class="%4$s"><a href="%5$s">Mi cuenta</a></li>
                    <li class="%4$s"><a href="%6$s" class="auth-menu__logout">Cerrar sesión</a></li>
                </ul>
            </%1$s>',
            tag_escape( $wrapper_tag ),
            esc_attr( implode( ' ', $wrapper_classes ) ),
            esc_html( $display_name ),
            esc_attr( $child_item_class ),
            esc_url( $account_url ),
            esc_url( $logout_url )
        );
    } else {
        // 🔁 Reemplaza $login_url con la URL de tu landing personalizada de login/registro.
        $login_url = wp_login_url();

        $markup = sprintf(
            '<%1$s class="%2$s">
                <a class="auth-menu__toggle auth-menu__link" href="%3$s">
                    <span class="auth-menu__label">Ingresar / Registrarse</span>
                </a>
            </%1$s>',
            tag_escape( $wrapper_tag ),
            esc_attr( implode( ' ', $wrapper_classes ) ),
            esc_url( $login_url )
        );
    }

    return $markup;
}

add_action( 'wp_footer', 'azimut_auth_menu_script', 50 );
/**
 * Prints the JavaScript that enables the dropdown behaviour.
 */
function azimut_auth_menu_script() {
    if ( ! is_user_logged_in() || ! azimut_has_auth_menu_rendered() ) {
        return;
    }
    ?>
    <script>
        (function () {
            const toggles = document.querySelectorAll('.auth-menu.menu-item-has-children .auth-menu__toggle');
            toggles.forEach((toggle) => {
                const menuItem = toggle.closest('.auth-menu');
                const dropdown = menuItem ? menuItem.querySelector('.auth-menu__dropdown') : null;

                if (!dropdown) {
                    return;
                }

                const closeDropdown = () => {
                    dropdown.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                    menuItem.classList.remove('is-open');
                };

                const openDropdown = () => {
                    dropdown.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    menuItem.classList.add('is-open');
                };

                toggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    const isOpen = !dropdown.hidden;
                    if (isOpen) {
                        closeDropdown();
                    } else {
                        openDropdown();
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!menuItem.contains(event.target)) {
                        closeDropdown();
                    }
                });

                dropdown.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', () => {
                        closeDropdown();
                    });
                });
            });
        })();
    </script>
    <?php
}

add_action( 'wp_footer', 'azimut_auth_menu_styles', 5 );
/**
 * Prints placeholder styles (near the footer) that can be customized to match the site.
 */
function azimut_auth_menu_styles() {
    if ( ! azimut_has_auth_menu_rendered() ) {
        return;
    }

    static $printed = false;

    if ( $printed ) {
        return;
    }

    $printed = true;
    ?>
    <style>
        .auth-menu {
            position: relative;
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .auth-menu.auth-menu--standalone {
            display: inline-flex;
        }

        .auth-menu .auth-menu__link,
        .auth-menu .auth-menu__toggle {
            text-decoration: none;
            background: var(--color-accent, transparent);
            border: none;
            cursor: pointer;
            color: inherit;
            font: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .auth-menu .auth-menu__icon {
            transition: transform 0.2s ease;
        }

        .auth-menu.menu-item-has-children .auth-menu__dropdown {
            position: absolute;
            top: calc(100% + 0.35rem);
            right: 0;
            min-width: 200px;
            margin: 0;
            padding: 0.75rem 0;
            list-style: none;
            background: var(--color-surface, #fff);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12);
            border-radius: 0.5rem;
            z-index: 999;
        }

        .auth-menu.menu-item-has-children .auth-menu__dropdown .auth-menu__item a {
            display: block;
            padding: 0.5rem 1rem;
            color: inherit;
        }

        .auth-menu.menu-item-has-children .auth-menu__dropdown .auth-menu__item a:hover,
        .auth-menu.menu-item-has-children .auth-menu__dropdown .auth-menu__item a:focus {
            background: var(--color-accent-soft, rgba(0, 0, 0, 0.05));
        }

        .auth-menu.is-open .auth-menu__icon {
            transform: rotate(180deg);
        }

        .auth-menu.menu-item-has-children .auth-menu__dropdown[hidden] {
            display: none !important;
        }
    </style>
    <?php
}
