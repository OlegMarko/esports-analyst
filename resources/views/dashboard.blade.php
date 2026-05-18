<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <livewire:match-dashboard />
        <livewire:match-analysis />
    </div>
</x-layouts::app>
