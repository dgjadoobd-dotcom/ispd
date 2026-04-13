<?php
    class BkashPersonalGateway
    {
        public function info()
        {
            return [
                'title'       => 'Bkash Personal',
                'logo'        => 'assets/logo.jpg',
                'currency'        => 'BDT',
                'tab'        => 'mfs',

                'gateway_type'        => 'automation',
                'sender_key'        => 'bkash',
                'sender_type'        => 'Personal',
            ];
        }

        public function color()
        {
            return [
                'primary_color'        => '#D12053',
                'text_color'        => '#FFFFFF',
                'btn_color'        => '#D12053',
                'btn_text_color'        => '#FFFFFF',
            ];
        }

        public function fields()
        {
            return [
                [
                    'name'  => 'qr_code',
                    'label' => 'Qr Code',
                    'type'  => 'image',
                ]
            ];
        }

        public function supported_languages()
        {
            return [
                'en' => 'English',
                'bn' => 'বাংলা',
            ];
        }

        public function lang_text()
        {
            return [
                '1' => [
                    'en' => 'Go to your bKash Mobile App.',
                    'bn' => 'আপনার বিকাশ মোবাইল অ্যাপে যান।',
                ],

                '2' => [
                    'en' => 'Choose "Send Money"',
                    'bn' => '“Send Money” নির্বাচন করুন',
                ],

                '3' => [
                    'en' => 'Enter the Number: {mobile_number}',
                    'bn' => 'নম্বর লিখুন: {mobile_number}',
                ],

                '4' => [
                    'en' => 'Or Scan the QR Code',
                    'bn' => 'অথবা কিউআর কোড স্ক্যান করুন',
                ],

                '5' => [
                    'en' => 'Enter the Amount: {amount} {currency}',
                    'bn' => 'পরিমাণ লিখুন: {amount} {currency}',
                ],

                '6' => [
                    'en' => 'Now enter your bKash PIN to confirm.',
                    'bn' => 'এখন নিশ্চিত করতে আপনার বিকাশ পিন লিখুন।',
                ],

                '7' => [
                    'en' => 'Put the Transaction ID in the box below and press Verify',
                    'bn' => 'ট্রানজ্যাকশন আইডি নিচের বক্সে লিখুন এবং যাচাই করুন চাপুন।',
                ],
            ];
        }

        private function normalizePool(mixed $raw): array
        {
            if (is_array($raw)) {
                return array_values(array_filter($raw, fn($v) => $v !== ''));
            }

            if (is_string($raw) && $raw !== '') {
                if ($raw[0] === '[') {
                    $decoded = json_decode($raw, true);
                    return is_array($decoded) ? $decoded : [$raw];
                }
                return [$raw];
            }

            return [];
        }

        private function selectNumber(array $pool, array &$options, string $gatewayId, string $brandId): string
        {
            $count = count($pool);

            if ($count === 0) {
                return '';
            }

            if ($count === 1) {
                return $pool[0];
            }

            $counter  = (int)($options['rr_counter'] ?? 0);
            $selected = $pool[$counter % $count];
            $newCounter = ($counter + 1) % $count;

            // Persist the new counter to gateways_parameter
            global $db_prefix;
            $existing = json_decode(
                getData(
                    $db_prefix . 'gateways_parameter',
                    'WHERE gateway_id = "' . $gatewayId . '" AND brand_id = "' . $brandId . '" AND option_name = "rr_counter"'
                ),
                true
            );

            if (isset($existing['response'][0]['id'])) {
                $condition = "id = '" . $existing['response'][0]['id'] . "'";
                updateData($db_prefix . 'gateways_parameter', ['value', 'updated_date'], [(string)$newCounter, getCurrentDatetime('Y-m-d H:i:s')], $condition);
            } else {
                insertData(
                    $db_prefix . 'gateways_parameter',
                    ['brand_id', 'gateway_id', 'option_name', 'value', 'created_date', 'updated_date'],
                    [$brandId, $gatewayId, 'rr_counter', (string)$newCounter, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')]
                );
            }

            $options['rr_counter'] = $newCounter;

            return $selected;
        }

        public function instructions($data)
        {
            $options = $data['options'] ?? [];
            $transaction = $data['transaction'] ?? [];

            $pool         = $this->normalizePool($options['mobile_number'] ?? '');
            $mobileNumber = $this->selectNumber($pool, $options, $data['gateway']['gateway_id'] ?? '', $data['brand']['id'] ?? '');
            $qrCode = $options['qr_code'] ?? '';
            $localAmount = $transaction['local_net_amount'] ?? '';
            $localCurrency = $transaction['local_currency'] ?? ($this->info()['currency'] ?? 'BDT');

            return [
                [
                    'icon' => '',
                    'text' => '1',
                    'copy' => false,
                ],
                [
                    'icon' => '',
                    'text' => '2',
                    'copy' => false
                ],
                [
                    'icon' => '',
                    'text' => '3',
                    'copy' => true,
                    'value' => $mobileNumber,
                    'vars' => [
                        '{mobile_number}' => $mobileNumber
                    ]
                ],
                [
                    'icon' => '',
                    'text' => '4',
                    'action' => [
                        'type'  => 'image',
                        'label' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-qrcode"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M7 17l0 .01" /><path d="M14 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M7 7l0 .01" /><path d="M4 15a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M17 7l0 .01" /><path d="M14 14l3 0" /><path d="M20 14l0 .01" /><path d="M14 14l0 3" /><path d="M14 20l3 0" /><path d="M17 17l3 0" /><path d="M20 17l0 3" /></svg>',
                        'value' => $qrCode,
                    ]
                ],
                [
                    'icon' => '',
                    'text' => '5',
                    'copy' => true,
                    'value' => $localAmount,
                    'vars' => [
                        '{amount}' => $localAmount,
                        '{currency}' => $localCurrency
                    ]
                ],
                [
                    'icon' => '',
                    'text' => '6',
                    'copy' => false
                ],
                [
                    'icon' => '',
                    'text' => '7',
                    'copy' => false
                ],


            ];
        }
    }
