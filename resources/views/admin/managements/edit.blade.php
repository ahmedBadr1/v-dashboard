<x-admin-app>
    @section('breadcrumb')
        <x-breadcrumb :tree="$tree" current="edit-management" ></x-breadcrumb>
    @endsection
    @livewire('managements.forms.management', ['management_id' => $management])
</x-admin-app>
