<?php

namespace Omnipay\Akbank\Message;

use JsonException;
use Omnipay\Akbank\Constants\ResponseCode;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Akbank Purchase Response
 *
 * Handles both non-3D (direct API response) and 3D (redirect to bank) responses.
 *
 * @property \Omnipay\Akbank\Message\PurchaseRequest $request
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
	protected $response;

	protected $request;

	protected bool $is3D = false;

	public function __construct(RequestInterface $request, $data)
	{
		parent::__construct($request, $data);

		$this->request = $request;
		$this->response = $data;

		if ($data instanceof ResponseInterface) {
			$body = (string) $data->getBody();

			try {
				$this->response = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				$this->response = [
					'responseCode'    => 'ERROR',
					'responseMessage' => $body,
				];
			}
		} elseif (is_array($data) && isset($data['paymentModel'])) {
			$this->is3D = true;
			$this->response = $data;
		}
	}

	public function isSuccessful(): bool
	{
		if ($this->is3D) {
			return false;
		}

		return isset($this->response['responseCode'])
			&& $this->response['responseCode'] === ResponseCode::SUCCESS;
	}

	public function isRedirect(): bool
	{
		return $this->is3D;
	}

	public function getRedirectUrl()
	{
		if (!$this->is3D) {
			return null;
		}

		/** @var PurchaseRequest $request */
		$request = $this->getRequest();

		return $request->get3DEndpoint();
	}

	public function getRedirectMethod(): string
	{
		return 'POST';
	}

	public function getRedirectData(): array
	{
		return is_array($this->response) ? $this->response : [];
	}

	public function getTransactionReference(): ?string
	{
		if (is_array($this->response) && isset($this->response['transaction']['authCode'])) {
			return $this->response['transaction']['authCode'];
		}

		return null;
	}

	public function getMessage(): ?string
	{
		if (is_array($this->response) && isset($this->response['responseMessage'])) {
			return $this->response['responseMessage'];
		}

		return null;
	}

	public function getCode(): ?string
	{
		if (is_array($this->response) && isset($this->response['responseCode'])) {
			return $this->response['responseCode'];
		}

		return null;
	}

	public function getData()
	{
		return $this->response;
	}
}
