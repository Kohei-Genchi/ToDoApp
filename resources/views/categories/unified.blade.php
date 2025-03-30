{{-- resources/views/categories/unified.blade.php --}}
<x-app-layout>
    <div id="unified-categories-view" class="p-1 sm:p-2">
        <!-- Vue.js component will be mounted here -->
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dynamic import UnifiedCategoriesView component
            import('./components/UnifiedCategoriesView.vue')
                .then(module => {
                    Vue.createApp(module.default).mount('#unified-categories-view');
                })
                .catch(error => {
                    console.error('Error loading UnifiedCategoriesView component:', error);
                    document.getElementById('unified-categories-view').innerHTML = `
                        <div class="bg-red-100 p-4 rounded text-red-700">
                            カテゴリー管理コンポーネントの読み込みに失敗しました。ページを更新してください。
                        </div>
                    `;
                });
        });
    </script>
</x-app-layout>
