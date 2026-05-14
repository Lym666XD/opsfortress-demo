import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Business = {
    id: number;
    legal_name: string;
    trading_name: string;
};

type Props = {
    businesses: Business[];
    defaultBusinessId: number | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Workplaces', href: '/admin/workplaces' },
    { title: 'New', href: '/admin/workplaces/create' },
];

/**
 * Slice 1 form. Plain text inputs for lat/long and geofence radius —
 * map-picker UI is intentionally out of scope. Country defaults to AU on
 * the server side (StoreWorkplaceRequest::prepareForValidation).
 *
 * If there is exactly one business in the tenant, the business selector
 * collapses to a hidden input so the admin doesn't see a single-option
 * dropdown.
 */
export default function WorkplacesCreate({ businesses, defaultBusinessId }: Props) {
    const singleBusiness = businesses.length === 1 ? businesses[0] : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New workplace" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="New workplace"
                    description="Add a physical site where workers sign in and perform tasks."
                />

                <Form
                    action="/admin/workplaces"
                    method="post"
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {singleBusiness ? (
                                <input
                                    type="hidden"
                                    name="business_id"
                                    value={singleBusiness.id}
                                />
                            ) : (
                                <div className="grid gap-2">
                                    <Label htmlFor="business_id">
                                        Business <span className="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="business_id"
                                        name="business_id"
                                        defaultValue={defaultBusinessId ?? ''}
                                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        required
                                    >
                                        <option value="" disabled>
                                            Select a business…
                                        </option>
                                        {businesses.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.trading_name || b.legal_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.business_id} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    Name <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="e.g. Brisbane Site 2"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">Code</Label>
                                    <Input
                                        id="code"
                                        name="code"
                                        placeholder="e.g. BNE-02"
                                    />
                                    <InputError message={errors.code} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="classification">
                                        Classification
                                    </Label>
                                    <Input
                                        id="classification"
                                        name="classification"
                                        placeholder="standard"
                                        defaultValue="standard"
                                    />
                                    <InputError message={errors.classification} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="street_address">
                                    Street address
                                </Label>
                                <Input
                                    id="street_address"
                                    name="street_address"
                                    placeholder="100 Demo Street"
                                />
                                <InputError message={errors.street_address} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="suburb">Suburb</Label>
                                    <Input
                                        id="suburb"
                                        name="suburb"
                                        placeholder="South Brisbane"
                                    />
                                    <InputError message={errors.suburb} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="state">State</Label>
                                    <Input
                                        id="state"
                                        name="state"
                                        placeholder="QLD"
                                    />
                                    <InputError message={errors.state} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="postcode">Postcode</Label>
                                    <Input
                                        id="postcode"
                                        name="postcode"
                                        placeholder="4101"
                                    />
                                    <InputError message={errors.postcode} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="country">
                                        Country (ISO-2)
                                    </Label>
                                    <Input
                                        id="country"
                                        name="country"
                                        defaultValue="AU"
                                        maxLength={2}
                                    />
                                    <InputError message={errors.country} />
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="latitude">Latitude</Label>
                                    <Input
                                        id="latitude"
                                        name="latitude"
                                        type="number"
                                        step="0.0000001"
                                        placeholder="-27.4810"
                                    />
                                    <InputError message={errors.latitude} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="longitude">Longitude</Label>
                                    <Input
                                        id="longitude"
                                        name="longitude"
                                        type="number"
                                        step="0.0000001"
                                        placeholder="153.0244"
                                    />
                                    <InputError message={errors.longitude} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="geofence_radius_meters">
                                        Geofence (m)
                                    </Label>
                                    <Input
                                        id="geofence_radius_meters"
                                        name="geofence_radius_meters"
                                        type="number"
                                        min={1}
                                        max={10000}
                                        placeholder="100"
                                    />
                                    <InputError
                                        message={errors.geofence_radius_meters}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Create workplace
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href="/admin/workplaces">Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
