/**
 * This file contains a global Alpine.js data function that is loaded by header.php
 * This ensures the data function is available when Alpine.js initializes
 */

// Define global Alpine.js data function
// Using window.data ensures it's in the global scope
window.data = function() {
    function getThemeFromLocalStorage() {
        // if user already changed the theme, use it
        if (window.localStorage.getItem('dark')) {
            return JSON.parse(window.localStorage.getItem('dark'));
        }
        // else return their preferences
        return (
            !!window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches
        );
    }

    function setThemeToLocalStorage(value) {
        window.localStorage.setItem('dark', value);
    }

    return {
        dark: getThemeFromLocalStorage(),
        toggleTheme() {
            this.dark = !this.dark;
            setThemeToLocalStorage(this.dark);
        },
        isSideMenuOpen: false,
        toggleSideMenu() {
            this.isSideMenuOpen = !this.isSideMenuOpen;
        },
        closeSideMenu() {
            this.isSideMenuOpen = false;
        },
        isNotificationsMenuOpen: false,
        toggleNotificationsMenu() {
            this.isNotificationsMenuOpen = !this.isNotificationsMenuOpen;
        },
        closeNotificationsMenu() {
            this.isNotificationsMenuOpen = false;
        },
        isProfileMenuOpen: false,
        toggleProfileMenu() {
            this.isProfileMenuOpen = !this.isProfileMenuOpen;
        },
        closeProfileMenu() {
            this.isProfileMenuOpen = false;
        },
        isPagesMenuOpen: false,
        togglePagesMenu() {
            this.isPagesMenuOpen = !this.isPagesMenuOpen;
        },
        isTopicsMenuOpen: false,
        toggleTopicsMenu() {
            this.isTopicsMenuOpen = !this.isTopicsMenuOpen;
        },
        isDocumentsMenuOpen: false,
        toggleDocumentsMenu() {
            this.isDocumentsMenuOpen = !this.isDocumentsMenuOpen;
        },
        isUsersMenuOpen: false,
        toggleUsersMenu() {
            this.isUsersMenuOpen = !this.isUsersMenuOpen;
        },
        isTodoMenuOpen: false,
        toggleTodoMenu() {
            this.isTodoMenuOpen = !this.isTodoMenuOpen;
        },
        // Modal
        isModalOpen: false,
        trapCleanup: null,
        openModal() {
            this.isModalOpen = true;
            if (typeof focusTrap === 'function') {
                this.trapCleanup = focusTrap(document.querySelector('#modal'));
            }
        },
        closeModal() {
            this.isModalOpen = false;
            if (this.trapCleanup) {
                this.trapCleanup();
            }
        }
    };
};
