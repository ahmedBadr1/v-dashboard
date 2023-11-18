<x-admin-app>
    @section('breadcrumb')
        <x-breadcrumb :tree="$tree" current="departments" ></x-breadcrumb>
    @endsection
    @livewire('managements.tables.departments', ['management_id' => $management_id])
</x-admin-app>
