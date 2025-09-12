<x-app-layout>
    <x-slot name="pagetitle">Dashboard</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="app-content">
        <div class="container-fluid my-4">
            <h2 class="mb-4">📊 Ringkasan Dashboard</h2>

            

            
        </div>
    </div>

    <x-slot name="jscustom">
        
    </x-slot>
</x-app-layout>
