<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    private function parseRoles(?string $roles): array
    {
        return array_values(array_unique(array_filter(explode(';', (string) $roles))));
    }

    private function formatRoles(array $roles): string
    {
        $roles = array_values(array_unique(array_filter($roles)));

        return $roles ? ';' . implode(';', $roles) . ';' : '';
    }

    private function descendantIds(int $menuId): array
    {
        $ids = [];
        $children = Menu::where('parent_id', $menuId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->descendantIds($childId));
        }

        return $ids;
    }

    private function ancestorIds(int $menuId): array
    {
        $ids = [];
        $menu = Menu::find($menuId);

        while ($menu && $menu->parent_id) {
            $ids[] = (int) $menu->parent_id;
            $menu = Menu::find($menu->parent_id);
        }

        return $ids;
    }

    private function expandSelectedIds(array $ids): array
    {
        $expanded = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            $descendantIds = $this->descendantIds($id);

            $expanded = array_merge($expanded, $descendantIds ?: [$id]);
        }

        return array_values(array_unique($expanded));
    }

    private function expandDeselectedIds(array $ids): array
    {
        $expanded = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            $expanded[] = $id;
            $expanded = array_merge($expanded, $this->descendantIds($id));
        }

        return array_values(array_unique($expanded));
    }

    private function updateRoleForMenus(array $ids, string $role, bool $active): void
    {
        foreach (Menu::whereIn('id', $ids)->get() as $menu) {
            $roles = $this->parseRoles($menu->role);

            if ($active) {
                $roles[] = $role;
            } else {
                $roles = array_values(array_filter($roles, fn ($item) => $item !== $role));
            }

            $menu->role = $this->formatRoles($roles);
            $menu->save();
        }
    }

    public function index(Request $request): view
    {
        return view('master.menu.list', [
            'user' => $request->user(),
            'roles' =>  Role::with('permissions')->get(),
        ]);
    }
    public function datamenu($role){
        $menu=array();
        $data = Menu::orderBy('seq')->get();
        
        foreach ( $data as $value) {
            $level = $this->parseRoles($value->role);
            $descendantIds = $this->descendantIds((int) $value->id);
            $selected = !$descendantIds && in_array($role, $level, true);

            $hd=array(
                'text'=>$value->name,
                'id'=>$value->id,
                'icon'=>$value->icon,
                'parent'=>empty($value->parent_id)?'#':$value->parent_id,
                'state'=>
                array(
                    'opened'=>true,
                    'role'=>(empty($value->parent_id)?true:false),
                    'disabled'=>false,
                    'selected'=> $selected,
                    )
                );
            array_push( $menu,$hd);
            //return response()->json($level);
        }
        return response()->json($menu); 
    }
    public function update(Request $request){
        $request->validate([
            'gp' => 'required|string',
            'id' => 'nullable|integer|exists:menus,id',
            'aktif' => 'nullable',
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'integer|exists:menus,id',
            'deselected_ids' => 'nullable|array',
            'deselected_ids.*' => 'integer|exists:menus,id',
        ]);

        if ($request->has('selected_ids') || $request->has('deselected_ids')) {
            $deselectedIds = $this->expandDeselectedIds($request->input('deselected_ids', []));
            $selectedIds = $this->expandSelectedIds($request->input('selected_ids', []));

            if ($deselectedIds) {
                $this->updateRoleForMenus($deselectedIds, $request->gp, false);
            }

            if ($selectedIds) {
                $this->updateRoleForMenus($selectedIds, $request->gp, true);
            }

            return response()->json([
                'success' => true,
                'selected_ids' => $selectedIds,
                'deselected_ids' => $deselectedIds,
            ]);
        }

        $ids = $request->aktif === 'true'
            ? $this->expandSelectedIds([(int) $request->id])
            : $this->expandDeselectedIds([(int) $request->id]);

        $this->updateRoleForMenus($ids, $request->gp, $request->aktif === 'true');

        return response()->json([
            'success' => true,
            'ids' => $ids,
        ]); 
    }
}
