<div class="form-grid">
    <div class="field">
        <label for="name_ar">اسم القسم بالعربية</label>
        <input id="name_ar" name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" required>
    </div>

    <div class="field">
        <label for="name_en">اسم القسم بالإنجليزية</label>
        <input id="name_en" name="name_en" value="{{ old('name_en', $category->name_en) }}">
    </div>

    <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="apartments-rent">
    </div>

    <div class="field">
        <label for="group_key">المجموعة</label>
        <input id="group_key" name="group_key" value="{{ old('group_key', $category->group_key) }}" placeholder="services">
    </div>

    <div class="field">
        <label for="parent_id">القسم الأب</label>
        <select id="parent_id" name="parent_id">
            <option value="">بدون قسم أب</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                    {{ $parent->name_ar }} - {{ $parent->slug }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="icon">الأيقونة</label>
        <input id="icon" name="icon" value="{{ old('icon', $category->icon) }}" placeholder="home">
    </div>

    <div class="field">
        <label for="sort_order">ترتيب العرض</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
    </div>

    <div class="field">
        <label>الإعدادات</label>
        <label class="check-row">
            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->is_active ?? true))>
            <span>القسم مفعل</span>
        </label>
        <label class="check-row">
            <input name="supports_booking" type="checkbox" value="1" @checked(old('supports_booking', $category->supports_booking ?? true))>
            <span>يعتمد على الحجز والتقويم</span>
        </label>
    </div>

    <div class="field full">
        <label for="description_ar">الوصف بالعربية</label>
        <textarea id="description_ar" name="description_ar">{{ old('description_ar', $category->description_ar) }}</textarea>
    </div>

    <div class="field full">
        <label for="description_en">الوصف بالإنجليزية</label>
        <textarea id="description_en" name="description_en">{{ old('description_en', $category->description_en) }}</textarea>
    </div>

    <div class="field full">
        <label for="settings_json">Settings JSON</label>
        <textarea id="settings_json" name="settings_json" placeholder='{"show_calendar": true}'>{{ old('settings_json', $category->settings ? json_encode($category->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
    </div>
</div>
