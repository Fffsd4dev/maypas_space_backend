<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $role_id
 * @property string $password
 * @property string|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AdminRole $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereUpdatedAt($value)
 */
	class Admin extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $manage_properties
 * @property string $manage_accounts
 * @property string $manage_admins
 * @property string $manage_estate_manager
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Admin> $admins
 * @property-read int|null $admins_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereManageAccounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereManageAdmins($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereManageEstateManager($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereManageProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRole whereUpdatedAt($value)
 */
	class AdminRole extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $category_id
 * @property int $number_item
 * @property string $location
 * @property string $address
 * @property int|null $landlord_id
 * @property int|null $estate_manager_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApartmentLocation> $apartmentAtLocation
 * @property-read int|null $apartment_at_location_count
 * @property-read \App\Models\ApartmentCategory $category
 * @property-read \App\Models\EstateManager|null $estateManager
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereEstateManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereLandlordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereNumberItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Apartment whereUpdatedAt($value)
 */
	class Apartment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Apartment> $apartments
 * @property-read int|null $apartments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentCategory whereUpdatedAt($value)
 */
	class ApartmentCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $apartment_id
 * @property string $apartment_identifier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Apartment $apartment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation whereApartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation whereApartmentIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApartmentLocation whereUpdatedAt($value)
 */
	class ApartmentLocation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $no_rooms
 * @property string $no_bathrooms
 * @property string $no_toilets
 * @property string|null $area_size
 * @property string $furnished
 * @property string $serviced
 * @property string $newly_built
 * @property int $property_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Property $property
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereAreaSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereFurnished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereNewlyBuilt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereNoBathrooms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereNoRooms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereNoToilets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail wherePropertyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereServiced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereUpdatedAt($value)
 */
	class Detail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $estate_name
 * @property string $slug
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager whereEstateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstateManager whereUpdatedAt($value)
 */
	class EstateManager extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $feature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Property> $properties
 * @property-read int|null $properties_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereFeature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereUpdatedAt($value)
 */
	class Feature extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $initial_payment
 * @property string|null $monthly_payment
 * @property int|null $payment_duration
 * @property int $pricing_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Pricing $pricing
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereInitialPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereMonthlyPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment wherePaymentDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment wherePricingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereUpdatedAt($value)
 */
	class Installment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $phone_number
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property int|null $estate_manager_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereEstateManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Landlord whereUpdatedAt($value)
 */
	class Landlord extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $id_card
 * @property string|null $selfie_photo
 * @property string|null $cac
 * @property string|null $business_name
 * @property string|null $business_state
 * @property string|null $business_lga
 * @property string|null $about_business
 * @property string|null $business_services
 * @property string|null $business_address
 * @property string|null $logo
 * @property string|null $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereAboutBusiness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereBusinessAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereBusinessLga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereBusinessServices($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereBusinessState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereCac($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereIdCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereSelfiePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandlordAgent whereVerified($value)
 */
	class LandlordAgent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $property_id
 * @property string $filename
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Property $property
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media wherePropertyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUpdatedAt($value)
 */
	class Media extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number whereUpdatedAt($value)
 */
	class Number extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property string $type
 * @property string $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereUserId($value)
 */
	class Otp extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $price
 * @property string|null $currency
 * @property string|null $duration
 * @property int $property_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Installment|null $installment
 * @property-read \App\Models\Property $property
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing wherePropertyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereUpdatedAt($value)
 */
	class Pricing extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int $listed_by
 * @property string $purpose
 * @property string $available
 * @property string $verified
 * @property string $country
 * @property string $state
 * @property string $locality
 * @property string|null $area
 * @property string|null $street
 * @property string|null $youtube_video_link
 * @property string|null $instagram_video_link
 * @property int $type_id
 * @property int $sub_type_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Detail|null $detail
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Feature> $features
 * @property-read int|null $features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Pricing|null $pricing
 * @property-read \App\Models\PropertySubType $subType
 * @property-read \App\Models\PropertyType $type
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereInstagramVideoLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereListedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereLocality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereSubTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereYoutubeVideoLink($value)
 */
	class Property extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $property_sub_type
 * @property int $property_type_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PropertyType $propertyType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType wherePropertySubType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType wherePropertyTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertySubType whereUpdatedAt($value)
 */
	class PropertySubType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $property_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType wherePropertyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PropertyType whereUpdatedAt($value)
 */
	class PropertyType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $apartment_id
 * @property string|null $id_card
 * @property string|null $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereApartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereIdCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantDocument whereVerified($value)
 */
	class TenantDocument extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property string $deactivated
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $user_type_id
 * @property int $estate_manager_id
 * @property-read \App\Models\LandlordAgent|null $landlordAgent
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Property> $properties
 * @property-read int|null $properties_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\Models\UserType $user_type
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeactivated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEstateManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUserTypeId($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserType whereUpdatedAt($value)
 */
	class UserType extends \Eloquent {}
}

