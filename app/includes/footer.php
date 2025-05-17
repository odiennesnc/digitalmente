                </div>
            </main>
        </div>
    </div>
    <script>
        function data() {
            return {
                isSideMenuOpen: false,
                isTopicsMenuOpen: false,
                isDocumentsMenuOpen: false,
                isUsersMenuOpen: false,
                isProfileMenuOpen: false,
                dark: false,
                
                toggleSideMenu() {
                    this.isSideMenuOpen = !this.isSideMenuOpen;
                },
                closeSideMenu() {
                    this.isSideMenuOpen = false;
                },
                toggleTopicsMenu() {
                    this.isTopicsMenuOpen = !this.isTopicsMenuOpen;
                },
                toggleDocumentsMenu() {
                    this.isDocumentsMenuOpen = !this.isDocumentsMenuOpen;
                },
                toggleUsersMenu() {
                    this.isUsersMenuOpen = !this.isUsersMenuOpen;
                },
                toggleProfileMenu() {
                    this.isProfileMenuOpen = !this.isProfileMenuOpen;
                },
                closeProfileMenu() {
                    this.isProfileMenuOpen = false;
                },
                toggleTheme() {
                    this.dark = !this.dark;
                }
            };
        }

        $(document).ready(function() {
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Italian.json"
                    },
                    "responsive": true
                });
            }
        });
    </script>
</body>
</html>
