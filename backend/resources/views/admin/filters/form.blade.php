<div class="form-grid">
    <div class="field">
        <label for="key">مفتاح الفلتر</label>
        <input id="key" name="key" value="{{ old('key', $filter->key) }}" placeholder="rooms_count" required>
    </div>

    <div class="field">
        <label for="input_type">نوع الإدخال</label>
        <select id="input_type" name="input_type" required>
            @foreach ($inputTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('input_type', $filter->input_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="label_ar">الاسم بالعربية</label>
        <input id="label_ar" name="label_ar" value="{{ old('label_ar', $filter->label_ar) }}" required>
    </div>

    <div class="field">
        <label for="label_en">الاسم بالإنجليزية</label>
        <input id="label_en" name="label_en" value="{{ old('label_en', $filter->label_en) }}">
    </div>

    <div class="field">
        <label for="unit_ar">الوحدة بالعربية</label>
        <input id="unit_ar" name="unit_ar" value="{{ old('unit_ar', $filter->unit_ar) }}" placeholder="متر مربع">
    </div>

    <div class="field">
        <label for="unit_en">الوحدة بالإنجليزية</label>
        <input id="unit_en" name="unit_en" value="{{ old('unit_en', $filter->unit_en) }}" placeholder="sqm">
    </div>

    <div class="field">
        <label for="sort_order">ترتيب العرض</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $filter->sort_order ?? 0) }}">
    </div>

    <div class="field">
        <label>الإعدادات</label>
        <label class="check-row">
            <input name="is_required" type="checkbox" value="1" @checked(old('is_required', $filter->is_required))>
            <span>إجباري عند إضافة الخدمة</span>
        </label>
        <label class="check-row">
            <input name="is_filterable" type="checkbox" value="1" @checked(old('is_filterable', $filter->is_filterable ?? true))>
            <span>يظهر في فلاتر التطبيق</span>
        </label>
        <label class="check-row">
            <input name="is_sortable" type="checkbox" value="1" @checked(old('is_sortable', $filter->is_sortable))>
            <span>قابل للترتيب لاحقاً</span>
        </label>
    </div>

    <div class="field full">
        <label for="options_json">Options JSON</label>
        <textarea id="options_json" name="options_json" placeholder='{"values":[{"value":"furnished","label_ar":"مفروشة","label_en":"Furnished"}]}'>{{ old('options_json', $filter->options ? json_encode($filter->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
    </div>
</div>
