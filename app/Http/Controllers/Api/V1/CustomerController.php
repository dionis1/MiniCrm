<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateCustomerAction;
use App\Actions\DeleteContactAction;
use App\Actions\DeleteCustomerAction;
use App\Actions\LogCustomerActivityAction;
use App\Actions\SaveContactAction;
use App\Actions\UpdateCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\CustomerFilterRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\CustomerResource;
use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function index(CustomerFilterRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $q = Customer::where('user_id', $request->user()->id);
        if (! empty($data['q'])) {
            $q->where('name', 'like', '%'.$data['q'].'%');
        }
        if (! empty($data['tag'])) {
            $q->whereJsonContains('tags', $data['tag']);
        }
        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        return CustomerResource::collection($q->orderBy($data['sort'] ?? 'created_at', $data['direction'] ?? 'desc')
            ->orderBy('id')->paginate(20)->withQueryString());
    }

    public function store(CustomerRequest $request, CreateCustomerAction $action): JsonResponse
    {
        $customer = $action($request->user(), $request->validated());

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $customer): CustomerResource
    {
        $model = $this->owned($request, $customer);
        $model->load(['contacts' => fn ($q) => $q->where('user_id', $request->user()->id)]);

        return new CustomerResource($model);
    }

    public function update(CustomerRequest $request, int $customer, UpdateCustomerAction $action): CustomerResource
    {
        return new CustomerResource($action($request->user(), $this->owned($request, $customer), $request->validated()));
    }

    public function destroy(Request $request, int $customer, DeleteCustomerAction $action): JsonResponse
    {
        $action($request->user(), $this->owned($request, $customer));

        return response()->json(status: 204);
    }

    public function contacts(Request $request, int $customer): AnonymousResourceCollection
    {
        $model = $this->owned($request, $customer);

        return ContactResource::collection($model->contacts()
            ->where('user_id', $request->user()->id)->orderBy('first_name')->orderBy('id')->paginate(20));
    }

    public function addContact(ContactRequest $request, int $customer, SaveContactAction $action): JsonResponse
    {
        $contact = $action($request->user(), $this->owned($request, $customer), $request->validated());

        return (new ContactResource($contact))->response()->setStatusCode(201);
    }

    public function updateContact(ContactRequest $request, int $contact, SaveContactAction $action): ContactResource
    {
        $model = Contact::where('user_id', $request->user()->id)->findOrFail($contact);

        return new ContactResource($action($request->user(), $this->owned($request, $model->companie_id), $request->validated(), $model));
    }

    public function deleteContact(Request $request, int $contact, DeleteContactAction $action): JsonResponse
    {
        $action($request->user(), Contact::where('user_id', $request->user()->id)->findOrFail($contact));

        return response()->json(status: 204);
    }

    public function activities(Request $request, int $customer): JsonResponse
    {
        $this->owned($request, $customer);

        return response()->json(['data' => CrmActivity::where('user_id', $request->user()->id)
            ->where('subject_type', 'customer')->where('subject_id', $customer)
            ->latest()->limit(100)->get(['id', 'type', 'description', 'created_at'])]);
    }

    public function logActivity(ActivityRequest $request, int $customer, LogCustomerActivityAction $action): JsonResponse
    {
        $data = $request->validated();
        $action($request->user(), $this->owned($request, $customer), $data);

        return response()->json(['message' => 'Activity recorded.'], 201);
    }

    private function owned(Request $request, int $id): Customer
    {
        $record = Customer::where('user_id', $request->user()->id)->findOrFail($id);
        Gate::authorize('view', $record);

        return $record;
    }
}
