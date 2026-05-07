<x-mail::message>
# Hello {{ $data['name'] }},

{{ $data['message'] }}

---

## Rent Details

- **Apartment Unit:** {{ $data['apartment_unit_name'] }}
- **Location:** {{ $data['apartment_location'] }}
- **Address:** {{ $data['apartment_address'] }}
- **Estate Manager:** {{ $data['estate_manager_name'] }}
- **Days Remaining:** {{ $data['days_remaining'] }}

---

<x-mail::button :url="config('app.url')">
Login to Your Account
</x-mail::button>

Thanks,<br>
{{ $data['estate_manager_name'] }}
</x-mail::message>
