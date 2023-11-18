<x-admin-app>
    @section('breadcrumb')
        <x-breadcrumb :tree="$tree" current="create-management" ></x-breadcrumb>
    @endsection
    @livewire('managements.forms.management', ['branch_id' => $branch_id])
</x-admin-app>
