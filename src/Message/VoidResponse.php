<?php

namespace Omnipay\Akbank\Message;

use JsonException;
use Omnipay\Akbank\Constants\ResponseCode;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Akbank Void (Cancel) Response
 */
class VoidResponse extends AbstractResponse
{
	protected $response;

	protected $request;

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
		}
	}

	public function isSuccessful(): bool
	{
		return isset($this->response['responseCode'])
			&& $this->response['responseCode'] === ResponseCode::SUCCESS;
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

	public function getRedirectData()
	{
		return null;
	}

	public function getRedirectUrl(): string
	{
		return '';
	}
}
