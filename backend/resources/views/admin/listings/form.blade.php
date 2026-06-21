@php
    $selectedCategoryId = (string) old('category_id', $listing->category_id);
    $detailValues = old('details', $detailValues ?? []);
    $hotelRoomRows = old('hotel_rooms', $hotelRoomRows ?? []);
    $csvValue = fn ($value) => is_array($value) ? implode('، ', $value) : $value;
@endphp

<div class="form-grid">
    <div class="field">
        <label for="category_id">القسم</label>
        <select id="category_id" name="category_id" required>
            <option value="">اختر القسم</option>
            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    data-slug="{{ $category->slug }}"
                    data-group="{{ $category->group_key }}"
                    @selected($selectedCategoryId === (string) $category->id)
                >
                    {{ $category->name_ar }} - {{ $category->slug }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="status">الحالة</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $listing->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="title_ar">الاسم بالعربية</label>
        <input id="title_ar" name="title_ar" value="{{ old('title_ar', $listing->title_ar) }}" required>
    </div>

    <div class="field">
        <label for="title_en">الاسم بالإنجليزية</label>
        <input id="title_en" name="title_en" value="{{ old('title_en', $listing->title_en) }}">
    </div>

    <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $listing->slug) }}" placeholder="listing-name">
    </div>

    <div class="field">
        <label for="published_at">تاريخ النشر</label>
        <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', optional($listing->published_at)->format('Y-m-d\\TH:i')) }}">
    </div>

    <div class="field">
        <label for="country_id">الدولة</label>
        <select id="country_id" name="country_id">
            <option value="">اختر الدولة</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected((string) old('country_id', $listing->country_id) === (string) $country->id)>
                    {{ $country->name_ar }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="city_id">المدينة / المنطقة</label>
        <select id="city_id" name="city_id">
            <option value="">اختر المدينة</option>
            @foreach ($cities as $city)
                <option value="{{ $city->id }}" @selected((string) old('city_id', $listing->city_id) === (string) $city->id)>
                    {{ $city->name_ar }} - {{ $city->country?->name_ar }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="area_name_ar">اسم المنطقة بالعربية</label>
        <input id="area_name_ar" name="area_name_ar" value="{{ old('area_name_ar', $listing->area_name_ar) }}">
    </div>

    <div class="field">
        <label for="area_name_en">اسم المنطقة بالإنجليزية</label>
        <input id="area_name_en" name="area_name_en" value="{{ old('area_name_en', $listing->area_name_en) }}">
    </div>

    <div class="field">
        <label for="latitude">Latitude</label>
        <input id="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $listing->latitude) }}">
    </div>

    <div class="field">
        <label for="longitude">Longitude</label>
        <input id="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $listing->longitude) }}">
    </div>

    <div class="field">
        <label for="phone">رقم الهاتف</label>
        <input id="phone" name="phone" value="{{ old('phone', $listing->phone) }}">
    </div>

    <div class="field">
        <label for="whatsapp">رقم واتساب</label>
        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $listing->whatsapp) }}">
    </div>

    <div class="field">
        <label for="base_price">السعر</label>
        <input id="base_price" name="base_price" type="number" step="0.01" min="0" value="{{ old('base_price', $listing->base_price) }}">
    </div>

    <div class="field">
        <label for="currency_code">العملة</label>
        <input id="currency_code" name="currency_code" maxlength="3" value="{{ old('currency_code', $listing->currency_code ?: 'JOD') }}">
    </div>

    <div class="field">
        <label for="price_unit">وحدة السعر</label>
        <select id="price_unit" name="price_unit" required>
            @foreach ($priceUnits as $value => $label)
                <option value="{{ $value }}" @selected(old('price_unit', $listing->price_unit) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label>خيارات العرض</label>
        <label class="check-row">
            <input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $listing->is_featured))>
            <span>خدمة مميزة</span>
        </label>
    </div>

    <div class="field full">
        <label for="address_ar">العنوان بالعربية</label>
        <input id="address_ar" name="address_ar" value="{{ old('address_ar', $listing->address_ar) }}">
    </div>

    <div class="field full">
        <label for="address_en">العنوان بالإنجليزية</label>
        <input id="address_en" name="address_en" value="{{ old('address_en', $listing->address_en) }}">
    </div>

    <div class="field full">
        <label for="description_ar">الوصف بالعربية</label>
        <textarea id="description_ar" name="description_ar">{{ old('description_ar', $listing->description_ar) }}</textarea>
    </div>

    <div class="field full">
        <label for="description_en">الوصف بالإنجليزية</label>
        <textarea id="description_en" name="description_en">{{ old('description_en', $listing->description_en) }}</textarea>
    </div>
</div>

<div style="height: 18px"></div>

<div class="panel" style="padding: 0; border: 0">
    <h2 style="font-size: 18px; margin: 0 0 12px">الفلاتر الديناميكية حسب القسم</h2>
    <div class="muted" style="margin-bottom: 14px">اختر القسم أولاً، وستظهر الحقول المناسبة له فقط.</div>

    @foreach ($categories as $category)
        @php
            $isSelectedCategory = $selectedCategoryId === (string) $category->id;
        @endphp
        <div class="attribute-panel" data-category-id="{{ $category->id }}" {{ ! $isSelectedCategory ? 'hidden' : '' }}>
            @if ($category->filters->isEmpty())
                <div class="alert">لا توجد فلاتر ديناميكية لهذا القسم.</div>
            @else
                <div class="form-grid">
                    @foreach ($category->filters as $filter)
                        @php
                            $fieldName = "attributes[{$filter->key}]";
                            $oldKey = "attributes.{$filter->key}";
                            $storedValue = $attributeValues[$filter->key] ?? null;
                            $value = old($oldKey, $storedValue);
                            $disabled = ! $isSelectedCategory;
                            $options = data_get($filter->options, 'values', []);
                        @endphp

                        <div class="field">
                            <label for="attr_{{ $category->id }}_{{ $filter->key }}">
                                {{ $filter->label_ar }}
                                @if ($filter->unit_ar)
                                    <span class="muted">({{ $filter->unit_ar }})</span>
                                @endif
                                @if ($filter->is_required)
                                    <span class="muted">*</span>
                                @endif
                            </label>

                            @switch($filter->input_type)
                                @case('number')
                                @case('rating')
                                    <input
                                        id="attr_{{ $category->id }}_{{ $filter->key }}"
                                        name="{{ $fieldName }}"
                                        type="number"
                                        step="0.01"
                                        value="{{ $value }}"
                                        @disabled($disabled)
                                    >
                                    @break

                                @case('boolean')
                                    <input type="hidden" name="{{ $fieldName }}" value="0" @disabled($disabled)>
                                    <label class="check-row">
                                        <input
                                            id="attr_{{ $category->id }}_{{ $filter->key }}"
                                            name="{{ $fieldName }}"
                                            type="checkbox"
                                            value="1"
                                            @checked((bool) $value)
                                            @disabled($disabled)
                                        >
                                        <span>نعم</span>
                                    </label>
                                    @break

                                @case('select')
                                    <select
                                        id="attr_{{ $category->id }}_{{ $filter->key }}"
                                        name="{{ $fieldName }}"
                                        @disabled($disabled)
                                    >
                                        <option value="">اختر</option>
                                        @foreach ($options as $option)
                                            <option value="{{ $option['value'] ?? '' }}" @selected((string) $value === (string) ($option['value'] ?? ''))>
                                                {{ $option['label_ar'] ?? ($option['value'] ?? '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('multi_select')
                                    @php $selectedValues = (array) $value; @endphp
                                    <select
                                        id="attr_{{ $category->id }}_{{ $filter->key }}"
                                        name="{{ $fieldName }}[]"
                                        multiple
                                        @disabled($disabled)
                                    >
                                        @foreach ($options as $option)
                                            <option value="{{ $option['value'] ?? '' }}" @selected(in_array($option['value'] ?? '', $selectedValues, true))>
                                                {{ $option['label_ar'] ?? ($option['value'] ?? '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('date')
                                    <input
                                        id="attr_{{ $category->id }}_{{ $filter->key }}"
                                        name="{{ $fieldName }}"
                                        type="date"
                                        value="{{ $value }}"
                                        @disabled($disabled)
                                    >
                                    @break

                                @case('time')
                                    <input
                                        id="attr_{{ $category->id }}_{{ $filter->key }}"
                                        name="{{ $fieldName }}"
                                        type="time"
                                        value="{{ $value }}"
                                        @disabled($disabled)
                                    >
                                    @break

                                @default
                                    <input
                                        id="attr_{{ $category->id }}_{{ $filter->key }}"
                                        name="{{ $fieldName }}"
                                        value="{{ $value }}"
                                        @disabled($disabled)
                                    >
                            @endswitch

                            @if ($filter->label_en)
                                <div class="muted">{{ $filter->label_en }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>

<div style="height: 18px"></div>

<div class="panel" style="padding: 0; border: 0">
    <h2 style="font-size: 18px; margin: 0 0 12px">تفاصيل القسم</h2>
    <div class="muted" style="margin-bottom: 14px">هذه الحقول تظهر حسب نوع القسم، وتحفظ في جداول التفاصيل الخاصة حتى تظهر بشكل منظم داخل التطبيق.</div>

    <div class="detail-panel" data-detail-slugs="chalets" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل الشاليه</h3>
        <div class="form-grid">
            <div class="field"><label>المساحة</label><input name="details[chalet][area_size]" type="number" min="0" value="{{ data_get($detailValues, 'chalet.area_size') }}"></div>
            <div class="field"><label>عدد الغرف</label><input name="details[chalet][rooms_count]" type="number" min="0" value="{{ data_get($detailValues, 'chalet.rooms_count') }}"></div>
            <div class="field"><label>عدد الحمامات</label><input name="details[chalet][bathrooms_count]" type="number" min="0" value="{{ data_get($detailValues, 'chalet.bathrooms_count') }}"></div>
            <div class="field"><label>عدد الضيوف</label><input name="details[chalet][max_guests]" type="number" min="0" value="{{ data_get($detailValues, 'chalet.max_guests') }}"></div>
            <div class="field">
                <label>البركة</label>
                <input type="hidden" name="details[chalet][has_pool]" value="0">
                <label class="check-row"><input name="details[chalet][has_pool]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'chalet.has_pool'))><span>يوجد بركة</span></label>
            </div>
            <div class="field">
                <label>تدفئة البركة</label>
                <input type="hidden" name="details[chalet][pool_is_heated]" value="0">
                <label class="check-row"><input name="details[chalet][pool_is_heated]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'chalet.pool_is_heated'))><span>البركة مدفأة</span></label>
            </div>
        </div>
    </div>

    <div class="detail-panel" data-detail-slugs="sports-fields" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل الملعب</h3>
        <div class="form-grid">
            <div class="field">
                <label>نوع الملعب</label>
                <select name="details[sports_field][field_type]">
                    <option value="">اختر</option>
                    @foreach (['football' => 'كرة قدم', 'padel' => 'بادل', 'basketball' => 'سلة', 'tennis' => 'تنس', 'other' => 'أخرى'] as $value => $label)
                        <option value="{{ $value }}" @selected(data_get($detailValues, 'sports_field.field_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>داخلي</label>
                <input type="hidden" name="details[sports_field][is_indoor]" value="0">
                <label class="check-row"><input name="details[sports_field][is_indoor]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'sports_field.is_indoor'))><span>ملعب داخلي</span></label>
            </div>
            <div class="field"><label>نوع الأرضية</label><input name="details[sports_field][surface_type]" value="{{ data_get($detailValues, 'sports_field.surface_type') }}"></div>
            <div class="field"><label>السعة</label><input name="details[sports_field][capacity]" type="number" min="0" value="{{ data_get($detailValues, 'sports_field.capacity') }}"></div>
        </div>
    </div>

    <div class="detail-panel" data-detail-slugs="wedding-halls" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل صالة الأفراح</h3>
        <div class="form-grid">
            <div class="field"><label>السعة</label><input name="details[wedding_hall][capacity]" type="number" min="0" value="{{ data_get($detailValues, 'wedding_hall.capacity') }}"></div>
            <div class="field"><label>نوع القاعة</label><input name="details[wedding_hall][hall_type]" value="{{ data_get($detailValues, 'wedding_hall.hall_type') }}"></div>
            <div class="field">
                <label>المواقف</label>
                <input type="hidden" name="details[wedding_hall][has_parking]" value="0">
                <label class="check-row"><input name="details[wedding_hall][has_parking]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'wedding_hall.has_parking'))><span>يوجد مواقف</span></label>
            </div>
            <div class="field">
                <label>الضيافة</label>
                <input type="hidden" name="details[wedding_hall][has_catering]" value="0">
                <label class="check-row"><input name="details[wedding_hall][has_catering]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'wedding_hall.has_catering'))><span>يوجد ضيافة</span></label>
            </div>
        </div>
    </div>

    <div class="detail-panel" data-detail-slugs="wedding-supplies" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل مستلزمات الأفراح</h3>
        <div class="form-grid">
            <div class="field">
                <label>نوع العرض</label>
                <select name="details[wedding_supply][supply_type]">
                    @foreach (['product' => 'منتج', 'package' => 'باقة', 'service' => 'خدمة', 'other' => 'أخرى'] as $value => $label)
                        <option value="{{ $value }}" @selected(data_get($detailValues, 'wedding_supply.supply_type', 'product') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>الكمية المتاحة</label><input name="details[wedding_supply][quantity_available]" type="number" min="0" value="{{ data_get($detailValues, 'wedding_supply.quantity_available') }}"></div>
            <div class="field full"><label>محتويات الباقة</label><textarea name="details[wedding_supply][package_items]">{{ $csvValue(data_get($detailValues, 'wedding_supply.package_items')) }}</textarea></div>
        </div>
    </div>

    <div class="detail-panel" data-detail-slugs="cars" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل السيارة</h3>
        <div class="form-grid">
            <div class="field"><label>نوع السيارة</label><input name="details[car_rental][car_type]" value="{{ data_get($detailValues, 'car_rental.car_type') }}"></div>
            <div class="field"><label>الشركة</label><input name="details[car_rental][brand]" value="{{ data_get($detailValues, 'car_rental.brand') }}"></div>
            <div class="field"><label>الموديل</label><input name="details[car_rental][model]" value="{{ data_get($detailValues, 'car_rental.model') }}"></div>
            <div class="field"><label>السنة</label><input name="details[car_rental][year]" type="number" min="1950" value="{{ data_get($detailValues, 'car_rental.year') }}"></div>
            <div class="field"><label>عدد الركاب</label><input name="details[car_rental][seats_count]" type="number" min="1" value="{{ data_get($detailValues, 'car_rental.seats_count') }}"></div>
            <div class="field">
                <label>مع سائق</label>
                <input type="hidden" name="details[car_rental][with_driver]" value="0">
                <label class="check-row"><input name="details[car_rental][with_driver]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'car_rental.with_driver'))><span>متاح مع سائق</span></label>
            </div>
            <div class="field">
                <label>ناقل الحركة</label>
                <select name="details[car_rental][transmission]">
                    <option value="">اختر</option>
                    <option value="automatic" @selected(data_get($detailValues, 'car_rental.transmission') === 'automatic')>أوتوماتيك</option>
                    <option value="manual" @selected(data_get($detailValues, 'car_rental.transmission') === 'manual')>عادي</option>
                </select>
            </div>
        </div>
    </div>

    <div class="detail-panel" data-detail-slugs="buses" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل الحافلة</h3>
        <div class="form-grid">
            <div class="field"><label>عدد المقاعد</label><input name="details[bus_rental][seats_count]" type="number" min="1" value="{{ data_get($detailValues, 'bus_rental.seats_count') }}"></div>
            <div class="field"><label>نوع الحافلة</label><input name="details[bus_rental][bus_type]" value="{{ data_get($detailValues, 'bus_rental.bus_type') }}"></div>
            <div class="field">
                <label>مع سائق</label>
                <input type="hidden" name="details[bus_rental][with_driver]" value="0">
                <label class="check-row"><input name="details[bus_rental][with_driver]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'bus_rental.with_driver'))><span>مع سائق</span></label>
            </div>
            <div class="field">
                <label>التكييف</label>
                <input type="hidden" name="details[bus_rental][has_ac]" value="0">
                <label class="check-row"><input name="details[bus_rental][has_ac]" type="checkbox" value="1" @checked((bool) data_get($detailValues, 'bus_rental.has_ac'))><span>يوجد تكييف</span></label>
            </div>
        </div>
    </div>

    <div class="detail-panel" data-detail-slugs="hotels" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل الفندق</h3>
        <div class="form-grid">
            <div class="field"><label>عدد النجوم</label><input name="details[hotel][stars]" type="number" min="1" max="7" value="{{ data_get($detailValues, 'hotel.stars') }}"></div>
            <div class="field"><label>وقت الدخول</label><input name="details[hotel][check_in_time]" type="time" value="{{ data_get($detailValues, 'hotel.check_in_time') }}"></div>
            <div class="field"><label>وقت الخروج</label><input name="details[hotel][check_out_time]" type="time" value="{{ data_get($detailValues, 'hotel.check_out_time') }}"></div>
            <div class="field"><label>الخدمات</label><input name="details[hotel][services]" value="{{ $csvValue(data_get($detailValues, 'hotel.services')) }}" placeholder="wifi، parking، pool"></div>
        </div>

        <div class="toolbar" style="margin-top: 18px">
            <div>
                <h3 style="font-size: 16px; margin: 0 0 4px">غرف الفندق</h3>
                <div class="muted">أضف الغرف والأسعار. يمكن وضع رابط صورة خارجي للغرفة مؤقتاً.</div>
            </div>
            <button class="button secondary" type="button" data-add-row="hotel-rooms">إضافة غرفة</button>
        </div>

        <table data-table="hotel-rooms">
            <thead>
                <tr>
                    <th>اسم الغرفة</th>
                    <th>النوع</th>
                    <th>السعر</th>
                    <th>بالغين</th>
                    <th>أطفال</th>
                    <th>عدد الغرف</th>
                    <th>رابط الصورة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hotelRoomRows as $index => $row)
                    <tr>
                        <td>
                            <input name="hotel_rooms[{{ $index }}][name_ar]" value="{{ $row['name_ar'] ?? '' }}" placeholder="غرفة مزدوجة">
                            <input name="hotel_rooms[{{ $index }}][name_en]" value="{{ $row['name_en'] ?? '' }}" placeholder="English name" style="margin-top: 6px">
                            <textarea name="hotel_rooms[{{ $index }}][description_ar]" placeholder="وصف مختصر" style="margin-top: 6px">{{ $row['description_ar'] ?? '' }}</textarea>
                        </td>
                        <td><input name="hotel_rooms[{{ $index }}][room_type]" value="{{ $row['room_type'] ?? '' }}" placeholder="double / suite"></td>
                        <td>
                            <input name="hotel_rooms[{{ $index }}][price_per_night]" type="number" step="0.01" min="0" value="{{ $row['price_per_night'] ?? '' }}">
                            <input name="hotel_rooms[{{ $index }}][currency_code]" maxlength="3" value="{{ $row['currency_code'] ?? 'JOD' }}" style="margin-top: 6px">
                        </td>
                        <td><input name="hotel_rooms[{{ $index }}][capacity_adults]" type="number" min="0" value="{{ $row['capacity_adults'] ?? 2 }}"></td>
                        <td><input name="hotel_rooms[{{ $index }}][capacity_children]" type="number" min="0" value="{{ $row['capacity_children'] ?? 0 }}"></td>
                        <td><input name="hotel_rooms[{{ $index }}][total_rooms]" type="number" min="1" value="{{ $row['total_rooms'] ?? 1 }}"></td>
                        <td><input name="hotel_rooms[{{ $index }}][image_url]" value="{{ $row['image_url'] ?? '' }}" placeholder="https://..."></td>
                        <td><button class="button danger" type="button" data-remove-row>حذف</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="detail-panel" data-detail-slugs="tourism-offices,travel-agencies" hidden>
        <h3 style="font-size: 16px; margin: 0 0 12px">تفاصيل البرنامج السياحي</h3>
        <div class="form-grid">
            <div class="field"><label>بلد الوجهة</label><input name="details[tourism_program][destination_country]" value="{{ data_get($detailValues, 'tourism_program.destination_country') }}"></div>
            <div class="field"><label>مدينة الوجهة</label><input name="details[tourism_program][destination_city]" value="{{ data_get($detailValues, 'tourism_program.destination_city') }}"></div>
            <div class="field"><label>بلد الانطلاق</label><input name="details[tourism_program][departure_country]" value="{{ data_get($detailValues, 'tourism_program.departure_country') }}"></div>
            <div class="field"><label>مدينة الانطلاق</label><input name="details[tourism_program][departure_city]" value="{{ data_get($detailValues, 'tourism_program.departure_city') }}"></div>
            <div class="field"><label>مدة الرحلة بالأيام</label><input name="details[tourism_program][duration_days]" type="number" min="1" value="{{ data_get($detailValues, 'tourism_program.duration_days') }}"></div>
            <div class="field"><label>تاريخ الرحلة</label><input name="details[tourism_program][trip_date]" type="date" value="{{ data_get($detailValues, 'tourism_program.trip_date') }}"></div>
            <div class="field">
                <label>نوع الرحلة</label>
                <select name="details[tourism_program][trip_type]">
                    <option value="">اختر</option>
                    <option value="domestic" @selected(data_get($detailValues, 'tourism_program.trip_type') === 'domestic')>داخلية</option>
                    <option value="international" @selected(data_get($detailValues, 'tourism_program.trip_type') === 'international')>خارجية</option>
                </select>
            </div>
            <div class="field"><label>المقاعد المتاحة</label><input name="details[tourism_program][seats_available]" type="number" min="0" value="{{ data_get($detailValues, 'tourism_program.seats_available') }}"></div>
            <div class="field"><label>الخدمات المشمولة</label><input name="details[tourism_program][included_services]" value="{{ $csvValue(data_get($detailValues, 'tourism_program.included_services')) }}"></div>
            <div class="field"><label>أوقات الطيران</label><input name="details[tourism_program][flight_times]" value="{{ $csvValue(data_get($detailValues, 'tourism_program.flight_times')) }}"></div>
        </div>
    </div>

    <div class="detail-panel" data-detail-groups="real-estate,services,garden-nursery,entertainment-tourism" hidden>
        <div class="alert">هذا القسم يعتمد على الفلاتر الديناميكية أعلاه، لذلك ستظهر تفاصيله في التطبيق من نفس الحقول التي يديرها الأدمن.</div>
    </div>
</div>

<div style="height: 18px"></div>

<div class="panel" style="padding: 0; border: 0">
    <h2 style="font-size: 18px; margin: 0 0 12px">صور وفيديوهات الإعلان</h2>

    @if ($listing->exists && $listing->media->isNotEmpty())
        <div class="grid cols-3" style="margin-bottom: 14px">
            @foreach ($listing->media as $media)
                <div class="stat">
                    <div style="height: 120px; margin-bottom: 10px; background: #f2f4f7; border-radius: 6px; overflow: hidden">
                        @if ($media->media_type === 'video')
                            <video src="{{ $media->url }}" controls muted style="width: 100%; height: 100%; object-fit: cover"></video>
                        @else
                            <img src="{{ $media->url }}" alt="{{ $media->alt_text_ar ?: $listing->title_ar }}" style="width: 100%; height: 100%; object-fit: cover">
                        @endif
                    </div>
                    <div class="badge gray">{{ $media->media_type === 'video' ? 'فيديو' : 'صورة' }}</div>
                    <div class="muted" style="word-break: break-all; margin-top: 8px">{{ $media->path }}</div>
                    <label class="check-row">
                        <input name="cover_media_id" type="radio" value="{{ $media->id }}" @checked($media->is_cover)>
                        <span>غلاف الإعلان</span>
                    </label>
                    <label class="check-row">
                        <input name="delete_media_ids[]" type="checkbox" value="{{ $media->id }}">
                        <span>حذف الوسيط</span>
                    </label>
                </div>
            @endforeach
        </div>
    @endif

    <div class="field">
        <label for="uploaded_media">رفع صور أو فيديوهات جديدة</label>
        <input id="uploaded_media" name="uploaded_media[]" type="file" accept="image/*,video/*" multiple>
        <div class="muted">يمكن رفع أكثر من صورة وأكثر من فيديو للإعلان. الحد الحالي لكل ملف 50MB.</div>
    </div>

    <div style="height: 14px"></div>

    @if ($listing->exists && $listing->images->isNotEmpty())
        <h3 style="font-size: 16px; margin: 0 0 12px">الصور القديمة</h3>
        <div class="grid cols-3" style="margin-bottom: 14px">
            @foreach ($listing->images as $image)
                <div class="stat">
                    <div style="height: 92px; margin-bottom: 10px; background: #f2f4f7; border-radius: 6px; overflow: hidden">
                        <img src="{{ $image->url }}" alt="{{ $image->alt_text_ar ?: $listing->title_ar }}" style="width: 100%; height: 100%; object-fit: cover">
                    </div>
                    <div class="muted" style="word-break: break-all">{{ $image->path }}</div>
                    <label class="check-row">
                        <input name="cover_image_id" type="radio" value="{{ $image->id }}" @checked($image->is_cover)>
                        <span>صورة الغلاف</span>
                    </label>
                    <label class="check-row">
                        <input name="delete_image_ids[]" type="checkbox" value="{{ $image->id }}">
                        <span>حذف الصورة</span>
                    </label>
                </div>
            @endforeach
        </div>
    @endif

    <div class="field">
        <label for="uploaded_images">رفع صور قديمة التوافق</label>
        <input id="uploaded_images" name="uploaded_images[]" type="file" accept="image/*" multiple>
        <div class="muted">هذا الحقل يبقي التوافق مع الصور القديمة. يفضل استخدام حقل الصور والفيديوهات أعلاه.</div>
    </div>
</div>

<div style="height: 18px"></div>

<div class="panel" style="padding: 0; border: 0">
    <div class="toolbar">
        <div>
            <h2 style="font-size: 18px; margin: 0 0 4px">المميزات والإضافات</h2>
            <div class="muted">مثال: بركة، مواقف، إنترنت، خدمة توصيل.</div>
        </div>
        <button class="button secondary" type="button" data-add-row="features">إضافة ميزة</button>
    </div>

    @php
        $featureRows = old('features', $featureRows ?? []);
    @endphp

    <table data-table="features">
        <thead>
            <tr>
                <th>الاسم عربي</th>
                <th>القيمة عربي</th>
                <th>الاسم إنجليزي</th>
                <th>القيمة إنجليزي</th>
                <th>الترتيب</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($featureRows as $index => $row)
                <tr>
                    <td><input name="features[{{ $index }}][name_ar]" value="{{ $row['name_ar'] ?? '' }}"></td>
                    <td><input name="features[{{ $index }}][value_ar]" value="{{ $row['value_ar'] ?? '' }}"></td>
                    <td><input name="features[{{ $index }}][name_en]" value="{{ $row['name_en'] ?? '' }}"></td>
                    <td><input name="features[{{ $index }}][value_en]" value="{{ $row['value_en'] ?? '' }}"></td>
                    <td><input name="features[{{ $index }}][sort_order]" type="number" min="0" value="{{ $row['sort_order'] ?? $index }}"></td>
                    <td><button class="button danger" type="button" data-remove-row>حذف</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="height: 18px"></div>

<div class="panel" style="padding: 0; border: 0">
    <div class="toolbar">
        <div>
            <h2 style="font-size: 18px; margin: 0 0 4px">تقويم التوفر والحجوزات</h2>
            <div class="muted">استخدمه للأقسام التي تعتمد على الحجز. الأيام المحجوزة أو المغلقة ستظهر للتطبيق كغير متاحة.</div>
        </div>
        <button class="button secondary" type="button" data-add-row="calendar">إضافة يوم</button>
    </div>

    @php
        $calendarRows = old('calendar_dates', $calendarRows ?? []);
    @endphp

    <table data-table="calendar">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th>سعر خاص</th>
                <th>ملاحظة</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($calendarRows as $index => $row)
                <tr>
                    <td><input name="calendar_dates[{{ $index }}][date]" type="date" value="{{ $row['date'] ?? '' }}"></td>
                    <td>
                        <select name="calendar_dates[{{ $index }}][status]">
                            @foreach ($calendarStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(($row['status'] ?? 'available') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input name="calendar_dates[{{ $index }}][price_override]" type="number" step="0.01" min="0" value="{{ $row['price_override'] ?? '' }}"></td>
                    <td><input name="calendar_dates[{{ $index }}][note]" value="{{ $row['note'] ?? '' }}"></td>
                    <td><button class="button danger" type="button" data-remove-row>حذف</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    (function () {
        var categorySelect = document.getElementById('category_id');
        var panels = Array.prototype.slice.call(document.querySelectorAll('.attribute-panel'));
        var detailPanels = Array.prototype.slice.call(document.querySelectorAll('.detail-panel'));

        function csvIncludes(value, needle) {
            if (!value || !needle) return false;
            return value.split(',').map(function (item) { return item.trim(); }).indexOf(needle) !== -1;
        }

        function syncPanels() {
            var selected = categorySelect.value;
            var selectedOption = categorySelect.options[categorySelect.selectedIndex];
            var slug = selectedOption ? selectedOption.getAttribute('data-slug') : '';
            var group = selectedOption ? selectedOption.getAttribute('data-group') : '';

            panels.forEach(function (panel) {
                var active = panel.getAttribute('data-category-id') === selected;
                panel.hidden = !active;
                Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea')).forEach(function (control) {
                    control.disabled = !active;
                });
            });

            detailPanels.forEach(function (panel) {
                var active = csvIncludes(panel.getAttribute('data-detail-slugs'), slug)
                    || csvIncludes(panel.getAttribute('data-detail-groups'), group);

                panel.hidden = !active;
                Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea, button')).forEach(function (control) {
                    if (control.hasAttribute('data-add-row') || control.hasAttribute('data-remove-row')) {
                        control.disabled = !active;
                    } else {
                        control.disabled = !active;
                    }
                });
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', syncPanels);
            syncPanels();
        }

        function rowCount(tableName) {
            return document.querySelectorAll('[data-table="' + tableName + '"] tbody tr').length;
        }

        function featureRow(index) {
            return '' +
                '<tr>' +
                '<td><input name="features[' + index + '][name_ar]"></td>' +
                '<td><input name="features[' + index + '][value_ar]"></td>' +
                '<td><input name="features[' + index + '][name_en]"></td>' +
                '<td><input name="features[' + index + '][value_en]"></td>' +
                '<td><input name="features[' + index + '][sort_order]" type="number" min="0" value="' + index + '"></td>' +
                '<td><button class="button danger" type="button" data-remove-row>حذف</button></td>' +
                '</tr>';
        }

        function calendarRow(index) {
            return '' +
                '<tr>' +
                '<td><input name="calendar_dates[' + index + '][date]" type="date"></td>' +
                '<td><select name="calendar_dates[' + index + '][status]">' +
                @foreach ($calendarStatuses as $value => $label)
                    '<option value="{{ $value }}">{{ $label }}</option>' +
                @endforeach
                '</select></td>' +
                '<td><input name="calendar_dates[' + index + '][price_override]" type="number" step="0.01" min="0"></td>' +
                '<td><input name="calendar_dates[' + index + '][note]"></td>' +
                '<td><button class="button danger" type="button" data-remove-row>حذف</button></td>' +
                '</tr>';
        }

        function hotelRoomRow(index) {
            return '' +
                '<tr>' +
                '<td>' +
                '<input name="hotel_rooms[' + index + '][name_ar]" placeholder="غرفة مزدوجة">' +
                '<input name="hotel_rooms[' + index + '][name_en]" placeholder="English name" style="margin-top: 6px">' +
                '<textarea name="hotel_rooms[' + index + '][description_ar]" placeholder="وصف مختصر" style="margin-top: 6px"></textarea>' +
                '</td>' +
                '<td><input name="hotel_rooms[' + index + '][room_type]" placeholder="double / suite"></td>' +
                '<td>' +
                '<input name="hotel_rooms[' + index + '][price_per_night]" type="number" step="0.01" min="0">' +
                '<input name="hotel_rooms[' + index + '][currency_code]" maxlength="3" value="JOD" style="margin-top: 6px">' +
                '</td>' +
                '<td><input name="hotel_rooms[' + index + '][capacity_adults]" type="number" min="0" value="2"></td>' +
                '<td><input name="hotel_rooms[' + index + '][capacity_children]" type="number" min="0" value="0"></td>' +
                '<td><input name="hotel_rooms[' + index + '][total_rooms]" type="number" min="1" value="1"></td>' +
                '<td><input name="hotel_rooms[' + index + '][image_url]" placeholder="https://..."></td>' +
                '<td><button class="button danger" type="button" data-remove-row>حذف</button></td>' +
                '</tr>';
        }

        document.addEventListener('click', function (event) {
            var addButton = event.target.closest('[data-add-row]');
            var removeButton = event.target.closest('[data-remove-row]');

            if (addButton) {
                var tableName = addButton.getAttribute('data-add-row');
                var tbody = document.querySelector('[data-table="' + tableName + '"] tbody');
                var index = rowCount(tableName);

                if (tableName === 'features') {
                    tbody.insertAdjacentHTML('beforeend', featureRow(index));
                } else if (tableName === 'hotel-rooms') {
                    tbody.insertAdjacentHTML('beforeend', hotelRoomRow(index));
                } else {
                    tbody.insertAdjacentHTML('beforeend', calendarRow(index));
                }
            }

            if (removeButton) {
                var row = removeButton.closest('tr');
                if (row) {
                    row.remove();
                }
            }
        });
    })();
</script>
