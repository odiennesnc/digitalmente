                </div>
            </main>
        </div>
    </div>
    <script>
        // Alpine.js data function is now loaded from alpine-data.js
        // No need to redefine it here

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
