@php
    $hasChildren = ! $parent && $category->children->isNotEmpty();
@endphp
<tr data-category-row data-category-id="{{ $category->id }}" data-parent-row="{{ $parent?->id }}"
    data-search="{{ $category->name_ur }} {{ $category->name }} {{ $parent?->name_ur }} {{ $parent?->name }}"
    class="{{ $parent ? 'is-child' : '' }} {{ $category->is_active && ($parent?->is_active ?? true) ? '' : 'is-inactive' }}">
    <td>
        <div class="cloth-category-name-cell">
            @if ($parent)
                <span class="cloth-category-branch" aria-hidden="true">↲</span>
            @endif
            <span class="cloth-category-icon"><i class="fas {{ $parent ? 'fa-tag' : 'fa-folder' }}" aria-hidden="true"></i></span>
            <span>
                <span class="cloth-category-name">{{ $category->name_ur ?: $category->name }}</span>
                <span class="cloth-category-name-en" dir="ltr">{{ $parent ? $parent->name.\App\Models\ClothCategory::NAME_SEPARATOR : '' }}{{ $category->name }}</span>
            </span>
        </div>
    </td>
    <td><span class="cloth-category-pill is-unit">{{ $category->unitLabel() }}</span></td>
    <td><span class="cloth-category-pill is-count">{{ $category->cloths_count }} سیٹ</span></td>
    <td>
        @if (! $category->is_active)
            <span class="cloth-category-pill is-off">غیر فعال</span>
        @elseif ($parent && ! $parent->is_active)
            <span class="cloth-category-pill is-off">مین کیٹیگری غیر فعال</span>
        @else
            <span class="cloth-category-pill is-active">فعال</span>
        @endif
    </td>
    <td dir="ltr" class="text-right">{{ $category->sort_order }}</td>
    <td>
        <div class="dropdown cloth-category-actions">
            <button class="btn cloth-category-actions-toggle" type="button" id="clothCategoryActions{{ $category->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ $category->name_ur ?: $category->name }} کی کارروائیاں">
                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-left" aria-labelledby="clothCategoryActions{{ $category->id }}" dir="rtl">
                <button type="button" class="dropdown-item" data-cloth-category-edit
                    data-id="{{ $category->id }}"
                    data-name-ur="{{ $category->name_ur }}"
                    data-name="{{ $category->name }}"
                    data-parent-id="{{ $category->parent_id }}"
                    data-sort-order="{{ $category->sort_order }}"
                    data-has-children="{{ $hasChildren ? 1 : 0 }}"
                    data-has-cloths="{{ $category->cloths_count > 0 ? 1 : 0 }}"
                    data-unit="{{ $category->stock_unit }}"
                    data-action="{{ route('admin.clothcategory.update', $category->id) }}">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                    <span>کیٹیگری میں ترمیم کریں</span>
                </button>
                <form action="{{ route('admin.clothcategory.toggle', $category->id) }}" method="POST">
                    @csrf
                    @method('patch')
                    <button class="dropdown-item" type="submit">
                        <i class="fas {{ $category->is_active ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i>
                        <span>{{ $category->is_active ? 'غیر فعال کریں' : 'دوبارہ فعال کریں' }}</span>
                    </button>
                </form>
                <div class="dropdown-divider"></div>
                <form action="{{ route('admin.clothcategory.destroy', $category->id) }}" method="POST" data-confirm="کیا آپ واقعی یہ کیٹیگری حذف کرنا چاہتے ہیں؟">
                    @csrf
                    @method('delete')
                    <button class="dropdown-item cloth-category-delete" type="submit">
                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        <span>کیٹیگری حذف کریں</span>
                    </button>
                </form>
            </div>
        </div>
    </td>
</tr>
