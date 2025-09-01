@php
    // Categories are passed to this field from the controller
    $categories = $field['categories'] ?? [];
    // Existing data for the branch
    $existingCategories = $entry->categories ?? collect(); // Categories associated with the branch
    $existingProducts = $entry->products ?? collect(); // Products associated with the branch
@endphp

<div>
    <label>{{ $field['label'] }}</label>

    <!-- Categories -->
    <div id="categories-container">
        @foreach($categories as $category)
            @php
                $isChecked = $existingCategories->contains($category->id); // Check if the category is already linked
                $slotsValue = $existingCategories->where('id', $category->id)->first()->pivot->slots ?? ''; // Get slot value
                $isShownChecked = $existingCategories->where('id', $category->id)->first()->pivot->is_shown ?? false; // Get 'shown' checkbox value
            @endphp

            <div class="category-block">
                <!-- Category Checkbox -->
                <label>
                    <input type="checkbox" class="category-checkbox" name="categories[]" value="{{ $category->id }}" data-category-id="{{ $category->id }}" {{ $isChecked ? 'checked' : '' }}>
                    {{ $category->name }}
                </label>

                <!-- Shown Checkbox -->
                <div class="shown-checkbox" id="shown-for-category-{{ $category->id }}" style="{{ $isChecked ? '' : 'display: none;' }} margin-left: 20px;">
                    <label>
                        <input type="checkbox" name="is_shown[{{ $category->id }}]" {{ $isShownChecked ? 'checked' : '' }}>
                        Is Shown
                    </label>
                </div>
                
                <!-- Slot Input -->
                <div class="slot-input" id="slot-for-category-{{ $category->id }}" style="{{ $isChecked ? '' : 'display: none;' }} margin-left: 20px;">
                    <label>
                        Slot:
                        <input type="number" name="slots[{{ $category->id }}]" class="form-control" placeholder="Enter slots" value="{{ $slotsValue }}">
                    </label>
                </div>

                

                <!-- Products for the selected category -->
                <div class="product-block" id="products-for-category-{{ $category->id }}" style="{{ $isChecked ? '' : 'display: none;' }} margin-left: 20px;">
                    @forelse($category->products as $product)
                        @php
                            $productChecked = $existingProducts->contains($product->id); // Check if the product is already linked
                        @endphp
                        <label>
                            <input type="checkbox" name="products[{{ $category->id }}][]" value="{{ $product->id }}" {{ $productChecked ? 'checked' : '' }}>
                            {{ $product->name }}
                        </label><br>
                    @empty
                        <p>No products available</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('crud_fields_scripts')
<script>
    $(document).ready(function () {
        // Handle category checkbox click
        $('.category-checkbox').on('change', function () {
            const categoryId = $(this).data('category-id');
            const productBlock = $(`#products-for-category-${categoryId}`);
            const slotInput = $(`#slot-for-category-${categoryId}`);
            const shownCheckbox = $(`#shown-for-category-${categoryId}`);

            if ($(this).is(':checked')) {
                productBlock.show(); // Show products for selected category
                slotInput.show(); // Show slot input for the category
                shownCheckbox.show(); // Show 'shown' checkbox for the category
            } else {
                productBlock.hide(); // Hide products if category is unchecked
                slotInput.hide(); // Hide slot input if category is unchecked
                shownCheckbox.hide(); // Hide 'shown' checkbox if category is unchecked
                productBlock.find('input[type="checkbox"]').prop('checked', false); // Uncheck all products
                shownCheckbox.find('input[type="checkbox"]').prop('checked', false); // Uncheck the 'shown' checkbox
            }
        });
    });
</script>
@endpush

@push('crud_fields_styles')
<style>
    .category-block {
        margin-bottom: 10px;
    }

    .product-block {
        margin-left: 20px;
    }

    .slot-input, .shown-checkbox {
        margin-left: 20px;
        margin-top: 5px;
    }
</style>
@endpush
