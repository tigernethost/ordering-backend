<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="form-group">
    <label>{{ $field['label'] }}
        <small class="form-hint"> Do not set a slot if you want it to be unlimited.
        </small> 
    </label>
    <div class="checkbox-list" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
        @foreach($products as $product)
            <div class="checkbox-item" style="display: flex; justify-content: center; align-items: center; gap: 5px;">
                <div style="display: flex; gap: 10px;">
                    <!-- Set the value to the product's ID -->
                    <label for="product_{{ $product->id }}">{{ $product->name }}</label>
                    <input type="checkbox" id="product_{{ $product->id }}" class="product-checkbox" data-product-id="{{ $product->id }}" name="products[]" value="{{ $product->id }}">
                </div>

                <!-- Slot input field, hidden by default -->
                <div class="slot-input" id="slot_input_{{ $product->id }}" style="display: none;">
                    <input type="number" data-slot-input="true" name="slots[{{ $product->id }}]" placeholder="Enter slots" class="form-control" min="1" />
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    // jQuery to toggle input field visibility based on checkbox status
    $(document).ready(function() {
        $('.product-checkbox').on('change', function() {
            var productId = $(this).data('product-id');
            var slotInput = $('#slot_input_' + productId).find('[data-slot-input=true]');

            // Show or hide the input field for slots based on the checkbox status
            if ($(this).is(':checked')) {
                slotInput.closest('.slot-input').show();
                slotInput.attr('name', 'slots[' + productId + ']');
            } else {
                slotInput.closest('.slot-input').hide();
                slotInput.removeAttr('name').val(''); // Clear the value
            }
        });
    });
</script>
