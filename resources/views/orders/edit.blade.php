@extends('layouts.app')

@section('content')

@php
    $productsForJs = $products->map(function ($product) {
        return [
            'product_id' => $product->product_id,
            'name' => $product->name,
            'unit' => $product->unit,
            'price' => $product->price,
        ];
    })->values();

    $uRole = strtoupper(trim(auth()->user()->role->name ?? ''));
    $canEdit = in_array($uRole, ['ADMIN', 'SALES']);
@endphp

<div class="container">
    <h2>Editar Orden</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.update', $order->order_id) }}" method="POST">
        @csrf
        @method('PUT')

        <fieldset @if(!$canEdit) disabled @endif>

            <h4>Datos de la orden</h4>

            <div class="mb-3">
                <label class="form-label">Número de Factura</label>
                <input type="text" name="invoice_number" class="form-control"
                    value="{{ old('invoice_number', $order->invoice_number) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Cliente</label>
                <select name="customer_id" class="form-control" required>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->customer_id }}"
                            {{ old('customer_id', $order->customer_id) == $customer->customer_id ? 'selected' : '' }}>
                            {{ $customer->customer_number }} - {{ $customer->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha y Hora</label>
                <input type="datetime-local" name="order_datetime" class="form-control"
                    value="{{ old('order_datetime', \Carbon\Carbon::parse($order->order_datetime)->format('Y-m-d\TH:i')) }}"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select name="status" class="form-control" required>
                    @foreach(['ORDERED', 'IN_PROCESS', 'IN_ROUTE', 'DELIVERED', 'DELETED'] as $status)
                        <option value="{{ $status }}"
                            {{ old('status', $order->status) == $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Notas</label>
                <textarea name="notes" class="form-control">{{ old('notes', $order->notes) }}</textarea>
            </div>

            <hr>

            @if($order->deliveryAddress)
                <h4>Dirección de entrega</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Calle</label>
                        <input type="text" name="street" class="form-control"
                            value="{{ old('street', $order->deliveryAddress->street) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Número exterior</label>
                        <input type="text" name="ext_number" class="form-control"
                            value="{{ old('ext_number', $order->deliveryAddress->ext_number) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Número interior</label>
                        <input type="text" name="int_number" class="form-control"
                            value="{{ old('int_number', $order->deliveryAddress->int_number) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Colonia</label>
                        <input type="text" name="neighborhood" class="form-control"
                            value="{{ old('neighborhood', $order->deliveryAddress->neighborhood) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="city" class="form-control"
                            value="{{ old('city', $order->deliveryAddress->city) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Estado</label>
                        <input type="text" name="state" class="form-control"
                            value="{{ old('state', $order->deliveryAddress->state) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Código Postal</label>
                        <input type="text" name="zip" class="form-control"
                            value="{{ old('zip', $order->deliveryAddress->zip) }}">
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="form-label">Referencias</label>
                        <input type="text" name="references" class="form-control"
                            value="{{ old('references', $order->deliveryAddress->references) }}">
                    </div>
                </div>
            @endif

            <hr>

            <h4>Material del pedido</h4>

            <table class="table" id="items-table">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Precio Unitario</th>
                        <th>Importe</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                    <tr>
                        <td>
                            <select name="items[{{ $index }}][product_id]" class="form-control product-select" required>
                                <option value="">Seleccione un material</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->product_id }}"
                                        data-unit="{{ $product->unit }}"
                                        data-price="{{ $product->price }}"
                                        {{ $item->product_id == $product->product_id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text"
                                name="items[{{ $index }}][quantity]"
                                class="form-control quantity-input"
                                value="{{ (int) $item->quantity }}"
                                required>
                        </td>
                        <td>
                            <input type="text"
                                name="items[{{ $index }}][unit]"
                                class="form-control unit-input"
                                value="{{ $item->product->unit ?? '' }}" readonly>
                        </td>
                        <td>
                            <input type="text"
                                name="items[{{ $index }}][unit_price]"
                                class="form-control price-input"
                                value="{{ number_format($item->unit_price, 2) }}" readonly>
                        </td>
                        <td>
                            <input type="text"
                                class="form-control importe-input" readonly
                                value="${{ number_format($item->quantity * $item->unit_price, 2) }}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger" onclick="removeRow(this)">X</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                        <td colspan="2">
                            <input type="text" id="total-general" class="form-control" readonly value="$0.00">
                        </td>
                    </tr>
                </tfoot>
            </table>

        </fieldset>

        @if(in_array($uRole, ['ADMIN', 'ROUTE']))
            <hr>

            <h4>Evidencias (Fotos)</h4>

            @if($order->photos && $order->photos->count())
                <div id="gallery-items" class="d-flex gap-2 flex-wrap mb-3">
                    @foreach($order->photos as $photo)
                        <div class="text-center">
                            <a href="{{ $photo->url }}" target="_blank">
                                <img src="{{ $photo->url }}" alt="evidence" class="img-thumbnail" style="width:120px;height:120px;object-fit:cover;">
                            </a>
                            <div class="small text-muted">Evidencia</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div id="gallery-items" class="d-flex gap-2 flex-wrap mb-3"></div>
                <p class="text-muted">No hay evidencias cargadas.</p>
            @endif

            <h5>Previews (antes de subir)</h5>
            <div id="previews-show" class="d-flex gap-2 flex-wrap mb-3"></div>

            <div id="upload-panel-show" class="mb-4">
                <label class="form-label">Subir evidencias</label>

                <div id="drop-zone-photos" class="border rounded p-3 text-center" style="background:#fafafa;">
                    <p class="mb-2">Arrastra y suelta imágenes aquí, o haz click para seleccionar.</p>
                    <button type="button" id="select-files-show" class="btn btn-outline-primary btn-sm">Seleccionar archivos</button>
                    <input id="photo-input-show" type="file" name="photos[]" accept="image/*" multiple style="display:none;">
                </div>

                <div class="mt-2">
                    <button type="button" id="upload-btn-show" class="btn btn-primary btn-sm">Subir</button>
                    <span id="upload-status-show" class="ms-2"></span>
                </div>
            </div>
        @endif

        @if($canEdit)
            <button type="button" onclick="addRow()" class="btn btn-primary">
                Agregar material
            </button>

            <button type="submit" class="btn btn-success">Actualizar</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancelar</a>
        @else
            <div class="alert alert-info mt-3">Vista de solo lectura para tu rol.</div>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Volver</a>
        @endif

    </form>
</div>

@endsection

@section('scripts')
<script>
    let rowIndex = {{ $order->items->count() }};
    const products = @json($productsForJs);

    function buildProductOptions() {
        let options = '<option value="">Seleccione un material</option>';
        products.forEach(product => {
            options += `<option value="${product.product_id}"
                data-unit="${product.unit}"
                data-price="${product.price}">
                ${product.name}
            </option>`;
        });
        return options;
    }

    function addRow() {
        const tableBody = document.querySelector('#items-table tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-control product-select" required>
                    ${buildProductOptions()}
                </select>
            </td>
            <td>
                <input type="text"
                    name="items[${rowIndex}][quantity]"
                    class="form-control quantity-input"
                    placeholder="0"
                    required>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][unit]"
                    class="form-control unit-input" readonly>
            </td>
            <td>
                <input type="text"
                    name="items[${rowIndex}][unit_price]"
                    class="form-control price-input" readonly>
            </td>
            <td>
                <input type="text" class="form-control importe-input" readonly value="$0.00">
            </td>
            <td>
                <button type="button" class="btn btn-danger" onclick="removeRow(this)">X</button>
            </td>
        `;
        tableBody.appendChild(row);
        rowIndex++;
        attachListeners(row);
    }

    function removeRow(button) {
        const rows = document.querySelectorAll('#items-table tbody tr');
        if (rows.length > 1) {
            button.closest('tr').remove();
            recalcularTotal();
        }
    }

    function recalcularTotal() {
        let total = 0;
        document.querySelectorAll('#items-table tbody tr').forEach(row => {
            const qty = parseInt(row.querySelector('.quantity-input')?.value) || 0;
            const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
            const importe = qty * price;
            const importeInput = row.querySelector('.importe-input');
            if (importeInput) {
                importeInput.value = '$' + importe.toFixed(2);
            }
            total += importe;
        });
        document.getElementById('total-general').value = '$' + total.toFixed(2);
    }

    function soloEnteros(input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '-') {
                e.preventDefault();
            }
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = text.replace(/[^0-9]/g, '');
            recalcularTotal();
        });
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            recalcularTotal();
        });
    }

    function attachListeners(row) {
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');

        soloEnteros(quantityInput);

        productSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const unit = selected.getAttribute('data-unit') || '';
            const price = selected.getAttribute('data-price') || '0';
            row.querySelector('.unit-input').value = unit;
            row.querySelector('.price-input').value = parseFloat(price).toFixed(2);
            recalcularTotal();
        });

        quantityInput.addEventListener('input', recalcularTotal);
    }

    document.querySelectorAll('#items-table tbody tr').forEach(row => {
        attachListeners(row);
    });

    recalcularTotal();
</script>

<script>
    (function(){
        const dropZone = document.getElementById('drop-zone-photos');
        const input = document.getElementById('photo-input-show');
        const selectBtn = document.getElementById('select-files-show');
        const previews = document.getElementById('previews-show');
        const uploadBtn = document.getElementById('upload-btn-show');
        const statusSpan = document.getElementById('upload-status-show');
        const gallery = document.getElementById('gallery-items');

        let files = [];

        if (!dropZone || !input || !selectBtn || !previews || !uploadBtn || !statusSpan) return;

        function prevent(e){
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter','dragover','dragleave','drop'].forEach(evt => {
            dropZone.addEventListener(evt, prevent);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (!dt) return;
            handleFiles(Array.from(dt.files));
        });

        dropZone.addEventListener('click', () => input.click());

        selectBtn.addEventListener('click', (e) => {
            e.preventDefault();
            input.click();
        });

        input.addEventListener('change', (e) => {
            handleFiles(Array.from(e.target.files));
        });

        function handleFiles(selected){
            const images = selected.filter(f => f.type && f.type.startsWith('image/'));

            images.forEach(file => {
                files.push(file);

                const reader = new FileReader();
                const wrapper = document.createElement('div');
                wrapper.className = 'position-relative';

                reader.onload = (ev) => {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.style.width = '120px';
                    img.style.height = '120px';
                    img.style.objectFit = 'cover';
                    img.className = 'img-thumbnail';
                    wrapper.appendChild(img);
                    previews.appendChild(wrapper);
                };

                reader.readAsDataURL(file);
            });
        }

        uploadBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            if (files.length === 0) {
                statusSpan.textContent = 'Seleccione al menos una imagen.';
                return;
            }

            statusSpan.textContent = 'Subiendo...';

            const form = new FormData();
            files.forEach(f => form.append('photos[]', f));

            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const token = tokenMeta ? tokenMeta.getAttribute('content') : null;
            const url = '{{ route('orders.photos.store', ['id' => $order->order_id]) }}';

            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: token ? {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    } : {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: form,
                    credentials: 'same-origin'
                });

                if (resp.redirected) {
                    statusSpan.textContent = 'Sesión expirada o no autenticado. Por favor, reingrese.';
                    return;
                }

                if (resp.status === 419) {
                    statusSpan.textContent = 'Token CSRF inválido (419). Recarga la página e inténtalo de nuevo.';
                    return;
                }

                const contentType = resp.headers.get('Content-Type') || '';

                if (!resp.ok) {
                    if (contentType.includes('application/json')) {
                        const err = await resp.json().catch(() => ({ message: 'Error en subida' }));
                        statusSpan.textContent = err.message || 'Error subiendo archivos.';
                    } else {
                        statusSpan.textContent = 'Error en la subida.';
                    }
                    return;
                }

                if (!contentType.includes('application/json')) {
                    statusSpan.textContent = 'Respuesta inesperada del servidor.';
                    return;
                }

                const data = await resp.json();
                statusSpan.textContent = data.message || 'Subida correcta.';

                if (data.data && Array.isArray(data.data)) {
                    data.data.forEach(p => {
                        const w = document.createElement('div');
                        w.className = 'text-center';

                        const a = document.createElement('a');
                        a.href = p.url;
                        a.target = '_blank';

                        const img = document.createElement('img');
                        img.src = p.url;
                        img.className = 'img-thumbnail';
                        img.style.width = '120px';
                        img.style.height = '120px';
                        img.style.objectFit = 'cover';

                        a.appendChild(img);
                        w.appendChild(a);

                        const meta = document.createElement('div');
                        meta.className = 'small text-muted';
                        meta.textContent = 'Evidencia';
                        w.appendChild(meta);

                        gallery.appendChild(w);
                    });
                }

                files = [];
                previews.innerHTML = '';
                input.value = '';
            } catch (err) {
                statusSpan.textContent = 'Ocurrió un error durante la subida.';
            }
        });
    })();
</script>
@endsection