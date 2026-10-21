<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ManageCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\CustomerResource;
use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['name', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
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
            ->paginate(20)->withQueryString());
    }

    public function store(CustomerRequest $request, ManageCustomer $action): JsonResponse
    {
        $customer = $action->create($request->user(), $request->validated());

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $customer): CustomerResource
    {
        $model = $this->owned($request, $customer);
        $model->load(['contacts' => fn ($q) => $q->where('user_id', $request->user()->id)]);

        return new CustomerResource($model);
    }

    public function update(CustomerRequest $request, int $customer, ManageCustomer $action): CustomerResource
    {
        return new CustomerResource($action->update($request->user(), $this->owned($request, $customer), $request->validated()));
    }

    public function destroy(Request $request, int $customer): JsonResponse
    {
        $this->owned($request, $customer)->delete();

        return response()->json(status: 204);
    }

    public function contacts(Request $request, int $customer): AnonymousResourceCollection
    {
        $model = $this->owned($request, $customer);

        return ContactResource::collection($model->contacts()
            ->where('user_id', $request->user()->id)->orderBy('first_name')->paginate(20));
    }

    public function addContact(ContactRequest $request, int $customer, ManageCustomer $action): JsonResponse
    {
        $contact = $action->contact($request->user(), $this->owned($request, $customer), $request->validated());

        return (new ContactResource($contact))->response()->setStatusCode(201);
    }

    public function updateContact(ContactRequest $request, int $contact): ContactResource
    {
        $model = Contact::where('user_id', $request->user()->id)->findOrFail($contact);
        $model->update($request->validated());

        return new ContactResource($model->refresh());
    }

    public function deleteContact(Request $request, int $contact): JsonResponse
    {
        Contact::where('user_id', $request->user()->id)->findOrFail($contact)->delete();

        return response()->json(status: 204);
    }

    public function activities(Request $request, int $customer): JsonResponse
    {
        $this->owned($request, $customer);

        return response()->json(['data' => CrmActivity::where('user_id', $request->user()->id)
            ->where('subject_type', 'customer')->where('subject_id', $customer)
            ->latest()->limit(100)->get(['id', 'type', 'description', 'created_at'])]);
    }

    public function logActivity(Request $request, int $customer, ManageCustomer $action): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['call', 'note', 'email', 'meeting'])],
            'description' => ['required', 'string', 'max:3000'],
        ]);
        $action->note($request->user(), $this->owned($request, $customer), $data['type'], $data['description']);

        return response()->json(['message' => 'Activity recorded.'], 201);
    }

    private function owned(Request $request, int $id): Customer
    {
        return Customer::where('user_id', $request->user()->id)->findOrFail($id);
    }
}
