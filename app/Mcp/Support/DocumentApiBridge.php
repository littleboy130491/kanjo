<?php

namespace App\Mcp\Support;

use App\Exceptions\DocumentApiConfigurationException;
use App\Models\User;
use App\Services\DocumentApi\DocumentApiAuthor;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Runs Document API v1 controller actions on behalf of the connected MCP user, so MCP tools
 * share the REST API's validation, services, and response shapes.
 */
class DocumentApiBridge
{
    /**
     * @param  Closure(): (JsonResponse|array<string, mixed>)  $action
     */
    public static function run(Request $request, Closure $action): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Not authenticated. Reconnect the Kanjo connector.');
        }

        try {
            $result = DocumentApiAuthor::actingAs($user, $action);
        } catch (HttpResponseException $exception) {
            return Response::error((string) $exception->getResponse()->getContent());
        } catch (ModelNotFoundException $exception) {
            $type = Str::snake(class_basename($exception->getModel()));

            return Response::error(sprintf(
                'No %s with id %s. Look it up with search_records instead of guessing ids.',
                $type,
                implode(', ', $exception->getIds()),
            ));
        } catch (ValidationException $exception) {
            return Response::error(self::json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ]));
        } catch (AuthorizationException|DocumentApiConfigurationException $exception) {
            return Response::error($exception->getMessage());
        } catch (HttpExceptionInterface $exception) {
            return Response::error($exception->getMessage() !== ''
                ? $exception->getMessage()
                : 'Request failed with HTTP '.$exception->getStatusCode().'.');
        }

        $data = $result instanceof JsonResponse ? $result->getData(true) : $result;

        return Response::structured($data);
    }

    /**
     * Apply the same model policy the admin panel uses. Models without a policy are allowed, as in Filament.
     *
     * @param  Model|class-string<Model>  $target
     *
     * @throws AuthorizationException
     */
    public static function authorize(Request $request, string $ability, Model|string $target): void
    {
        if (Gate::getPolicyFor($target) === null || Gate::forUser($request->user())->allows($ability, $target)) {
            return;
        }

        $type = Str::snake(class_basename($target));

        throw new AuthorizationException("Your Kanjo role is not allowed to {$ability} {$type} records.");
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function query(array $query = []): HttpRequest
    {
        return HttpRequest::create('/', 'GET', array_filter(
            $query,
            fn (mixed $value): bool => $value !== null && $value !== '',
        ));
    }

    /**
     * Build and validate an API form request from a JSON body, exactly as the HTTP API would.
     *
     * @template TRequest of FormRequest
     *
     * @param  class-string<TRequest>  $class
     * @param  array<string, mixed>  $payload
     * @return TRequest
     */
    public static function form(string $class, array $payload): FormRequest
    {
        $request = $class::create('/', 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], content: self::json($payload));

        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        return $request;
    }

    /**
     * Merge the tool's dry_run flag into an API payload.
     *
     * @return array<string, mixed>
     */
    public static function payload(Request $request): array
    {
        $payload = $request->get('payload', []);
        $payload = is_array($payload) ? $payload : [];

        if ($request->boolean('dry_run')) {
            $payload['dry_run'] = true;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function json(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
