import { rangePresets } from "@/constants/range-date";
import { IConnection } from "@/features/connect/manage-connect/types";
import { useSetting } from "@/hooks/use-setting";
import { handleApiError } from "@/lib/api-error";
import axiosInstance from "@/lib/axios-instance";
import { formatDateDDMMYYYY, generateBaseApiUrl } from "@/lib/utils";
import { useUser } from "@/providers/user-context";
import { LinkOutlined, UserOutlined } from "@ant-design/icons";
import { useQuery } from "@tanstack/react-query";
import {
  Button,
  Card,
  Col,
  DatePicker,
  Divider,
  Flex,
  Form,
  Input,
  InputNumber,
  message,
  Row,
  Space,
  Spin,
  Table,
  Typography,
} from "antd";
import { useTranslations } from "next-intl";
import React from "react";
import {
  createTransactionTicket,
  getTransactionTikets,
  IGetTransactionTicketParams,
  updateTransactionTicket,
} from "../../actions";
import ModalLinkedUser from "../../components/modal-linked-user";
import { ITransactionTicket, ITransactionTicketPayload } from "../../types";

const { RangePicker } = DatePicker;

export interface ISaleFormProps {
  sale?: ITransactionTicket | null;
  onBack: () => void;
  refetch: () => void;
}

const SaleForm = ({ refetch, sale, onBack }: ISaleFormProps) => {
  const t = useTranslations("TransactionTicket");
  const tCommon = useTranslations("Common");
  const [form] = Form.useForm();
  const { userInfo } = useUser();
  const { data: settingPriceData } = useSetting();

  const [isModalOpen, setIsModalOpen] = React.useState(false);
  const [activeLinkType, setActiveLinkType] = React.useState<
    "buyer" | "seller" | null
  >(null);

  const [purchaseParams, setPurchaseParams] = React.useState<
    Partial<IGetTransactionTicketParams>
  >({
    page: 1,
    limit: 10,
    transaction_ticket_type: "purchase",
    status: "completed",
    contract_code: "",
    end_date: "",
    start_date: "",
  });

  const { data: purchaseTicketsData, isFetching: isFetchingPurchaseTickets } =
    useQuery({
      queryKey: ["transaction-ticket-purchases-inline", purchaseParams],
      queryFn: () => getTransactionTikets(purchaseParams),
    });

  const { data: linkedPurchasesData } = useQuery({
    queryKey: ["linked-purchase-tickets", sale?.transaction_ticket_code],
    queryFn: async () => {
      const url =
        generateBaseApiUrl() +
        `/v1/transaction-ticket/${sale?.transaction_ticket_code}/purchase-ticket/?page=1&limit=100`;
      const res = await axiosInstance.get(url);
      return res.data;
    },
    enabled: !!sale?.transaction_ticket_code,
  });

  const [selectedPurchaseKeys, setSelectedPurchaseKeys] = React.useState<
    React.Key[]
  >([]);
  console.log(linkedPurchasesData);
  React.useEffect(() => {
    if (linkedPurchasesData?.data?.records) {
      // Lấy ra mảng các ID từ danh sách trả về
      const linkedIds = linkedPurchasesData.data.records.map(
        (ticket: any) => ticket.transaction_ticket_id,
      );

      // Auto check vào table
      setSelectedPurchaseKeys(linkedIds);

      form.setFieldsValue({
        purchase_ticket_ids: linkedIds,
      });
    }
  }, [linkedPurchasesData, form]);

  const latexWeight = Form.useWatch("latex_weight", form);
  const latexTsc = Form.useWatch("latex_tsc_grade", form);
  const latexPrice = Form.useWatch("latex_price_per_tsc", form);

  React.useEffect(() => {
    const weight = Number(latexWeight) || 0;
    const tsc = Number(latexTsc) || 0;
    const price = Number(latexPrice) || 0;

    const total = weight * (tsc / 100) * price;
    form.setFieldsValue({
      latex_total_amount: Math.round(total),
    });
  }, [latexWeight, latexTsc, latexPrice, form]);

  // Tính toán tiền mủ tạp
  const scrapWeight = Form.useWatch("scrap_rubber_weight", form);
  const scrapDrc = Form.useWatch("scrap_rubber_drc_grade", form);
  const scrapPrice = Form.useWatch("scrap_rubber_price_per_drc", form);

  React.useEffect(() => {
    const weight = Number(scrapWeight) || 0;
    const drc = Number(scrapDrc) || 0;
    const price = Number(scrapPrice) || 0;

    const total = weight * (drc / 100) * price;
    form.setFieldsValue({
      scrap_rubber_total_amount: Math.round(total),
    });
  }, [scrapWeight, scrapDrc, scrapPrice, form]);

  React.useEffect(() => {
    if (sale) return;
    if (!settingPriceData?.data) return;

    const defaultLatexPrice = settingPriceData.data.find(
      (item) => item.setting_code === "latex_price_per_tsc_kg",
    )?.value;

    const defaultScrapPrice = settingPriceData.data.find(
      (item) => item.setting_code === "scrap_rubber_price_per_drc_kg",
    )?.value;

    form.setFieldsValue({
      latex_price_per_tsc: defaultLatexPrice || 0,
      scrap_rubber_price_per_drc: defaultScrapPrice || 0,
    });
  }, [settingPriceData, form, sale]);

  React.useEffect(() => {
    if (sale) {
      form.setFieldsValue(sale);
    }
  }, [sale, form]);

  const handleOpenModal = (type: "buyer" | "seller") => {
    setActiveLinkType(type);
    setIsModalOpen(true);
  };

  const handleSelectLinkedUser = (user: IConnection) => {
    user.connection_direction === "received"
      ? form.setFieldsValue({
          buyer_user_id: user.requester_user_id,
          buyer_name: user.full_name,
          buyer_phone: user.phone,
          buyer_account_type: user.register_type,
        })
      : form.setFieldsValue({
          buyer_user_id: user.target_user_id,
          buyer_name: user.full_name,
          buyer_phone: user.phone,
          buyer_account_type: user.register_type,
        });

    setIsModalOpen(false);
    setActiveLinkType(null);
  };

  const handleLinkSelf = () => {
    form.setFieldsValue({
      buyer_user_id: userInfo?.user_id,
      buyer_name: userInfo?.fullName,
      buyer_phone: userInfo?.phone,
      buyer_account_type: userInfo?.register_type,
    });
  };

  const handleSearchPurchase = (value: string) => {
    setPurchaseParams((prev) => ({ ...prev, contract_code: value, page: 1 }));
  };

  const handleDateChangePurchase = (dates: any) => {
    setPurchaseParams((prev) => ({
      ...prev,
      start_date: dates?.[0]?.format("YYYY-MM-DD") || undefined,
      end_date: dates?.[1]?.format("YYYY-MM-DD") || undefined,
      page: 1,
    }));
  };

  const purchaseColumns = [
    {
      title: t("ticket_code"),
      dataIndex: "transaction_ticket_code",
      key: "transaction_ticket_code",
      render: (text: string) => (
        <Typography.Text strong>{text.toUpperCase()}</Typography.Text>
      ),
    },
    {
      title: t("seller"),
      dataIndex: "seller_name",
      key: "seller_name",
    },
    {
      title: t("latex_weight_kg"),
      dataIndex: "latex_weight",
      key: "latex_weight",
    },
    {
      title: t("scrap_weight_kg"),
      dataIndex: "scrap_rubber_weight",
      key: "scrap_rubber_weight",
    },
    {
      title: tCommon("creation_date"),
      dataIndex: "created_at",
      key: "created_at",
      render: (date: string) => formatDateDDMMYYYY(date),
    },
  ];

  const [isSubmitting, setIsSubmitting] = React.useState(false);
  const handleSubmit = async (values: ITransactionTicketPayload) => {
    try {
      setIsSubmitting(true);
      if (sale) {
        await updateTransactionTicket(sale.transaction_ticket_code, {
          ...values,
          transaction_ticket_type: "sale",
        });
      } else {
        await createTransactionTicket({
          ...values,
          transaction_ticket_type: "sale",
        });
      }
      refetch();
      onBack();
      form.resetFields();
      message.success(
        sale ? t("sale.update_success") : t("sale.create_success"),
      );
    } catch (error) {
      handleApiError(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Spin spinning={isSubmitting}>
      <Card
        title={
          <Typography.Title level={4} style={{ margin: 0 }}>
            {sale ? t("sale.edit_title") : t("sale.add_title")}
          </Typography.Title>
        }
        extra={
          <Space>
            <Button
              className="text-xs!"
              type="link"
              onClick={() => handleOpenModal("buyer")}
              icon={<LinkOutlined />}
              disabled={!!sale}>
              {t("link_buyer")}
            </Button>
            <Divider orientation="vertical" />
            <Button
              className="text-xs!"
              type="link"
              onClick={() => handleLinkSelf()}
              icon={<UserOutlined />}
              disabled={!!sale}>
              {t("link_self_buyer")}
            </Button>
          </Space>
        }>
        <Form
          form={form}
          onFinish={handleSubmit}
          layout="vertical"
          initialValues={{
            seller_user_id: userInfo?.user_id,
            seller_name: userInfo?.fullName,
            seller_phone: userInfo?.phone,
            seller_account_type: userInfo?.register_type,
          }}>
          <Row gutter={24}>
            <Col span={24} md={12}>
              <Divider titlePlacement="left" style={{ marginTop: 0 }}>
                {t("buyer_info_title")}
              </Divider>

              <Row gutter={16}>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("buyer_name")}
                    name="buyer_name"
                    rules={[
                      {
                        required: true,
                        message: t("enter_buyer_name"),
                      },
                    ]}>
                    <Input placeholder={t("enter_buyer_name")} disabled />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={tCommon("phone_number")}
                    name="buyer_phone"
                    rules={[
                      {
                        required: true,
                        message: tCommon("phone_required"),
                      },
                    ]}>
                    <Input
                      placeholder={tCommon("phone_placeholder")}
                      disabled
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Form.Item
                label={tCommon("address")}
                name="buyer_address"
                rules={[
                  { required: true, message: tCommon("address_required") },
                ]}>
                <Input placeholder={tCommon("enter_address")} />
              </Form.Item>

              <Divider titlePlacement="left">{t("latex")}</Divider>
              <Row gutter={16}>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("weight_kg_label")}
                    name="latex_weight"
                    rules={[{ required: true, message: t("enter_weight") }]}>
                    <InputNumber
                      min={0}
                      style={{ width: "100%" }}
                      placeholder={t("enter_weight")}
                    />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("tsc_grade")}
                    name="latex_tsc_grade"
                    rules={[{ required: true, message: t("enter_percent") }]}>
                    <InputNumber
                      min={0}
                      max={100}
                      style={{ width: "100%" }}
                      placeholder={t("enter_percent")}
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Row gutter={16}>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("unit_price_vnd")}
                    name="latex_price_per_tsc"
                    rules={[
                      { required: true, message: t("enter_unit_price") },
                    ]}>
                    <InputNumber
                      min={0}
                      style={{ width: "100%" }}
                      placeholder={t("enter_unit_price")}
                      formatter={(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                      }
                      parser={(value) =>
                        value!.replace(/\$\s?|(,*)/g, "") as any
                      }
                    />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("amount_vnd_label")}
                    name="latex_total_amount">
                    <InputNumber
                      min={0}
                      style={{ width: "100%" }}
                      placeholder={t("auto_calculate")}
                      formatter={(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                      }
                      parser={(value) =>
                        value!.replace(/\$\s?|(,*)/g, "") as any
                      }
                      disabled
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Form.Item label={t("latex_notes")} name="latex_notes">
                <Input.TextArea rows={2} placeholder={t("enter_notes")} />
              </Form.Item>
            </Col>

            <Col span={24} md={12}>
              <Divider titlePlacement="left" style={{ marginTop: 0 }}>
                {t("seller_info_title")}
              </Divider>

              <Row gutter={16}>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("seller_name")}
                    name="seller_name"
                    rules={[{ required: true }]}>
                    <Input placeholder={t("enter_seller_name")} disabled />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={tCommon("phone_number")}
                    name="seller_phone"
                    rules={[{ required: true }]}>
                    <Input
                      placeholder={tCommon("phone_placeholder")}
                      disabled
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Form.Item
                label={tCommon("address")}
                name="seller_address"
                rules={[
                  { required: true, message: tCommon("address_required") },
                ]}>
                <Input placeholder={tCommon("enter_address")} />
              </Form.Item>

              <Divider titlePlacement="left">{t("scrap_rubber")}</Divider>
              <Row gutter={16}>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("weight_kg_label")}
                    name="scrap_rubber_weight"
                    rules={[{ required: true, message: t("enter_weight") }]}>
                    <InputNumber
                      min={0}
                      style={{ width: "100%" }}
                      placeholder={t("enter_weight")}
                    />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("drc_grade")}
                    name="scrap_rubber_drc_grade"
                    rules={[{ required: true, message: t("enter_percent") }]}>
                    <InputNumber
                      min={0}
                      max={100}
                      style={{ width: "100%" }}
                      placeholder={t("enter_percent")}
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Row gutter={16}>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("unit_price_vnd")}
                    name="scrap_rubber_price_per_drc"
                    rules={[
                      { required: true, message: t("enter_unit_price") },
                    ]}>
                    <InputNumber
                      min={0}
                      style={{ width: "100%" }}
                      placeholder={t("enter_unit_price")}
                      formatter={(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                      }
                      parser={(value) =>
                        value!.replace(/\$\s?|(,*)/g, "") as any
                      }
                    />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={t("amount_vnd_label")}
                    name="scrap_rubber_total_amount">
                    <InputNumber
                      min={0}
                      style={{ width: "100%" }}
                      placeholder={t("auto_calculate")}
                      formatter={(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                      }
                      parser={(value) =>
                        value!.replace(/\$\s?|(,*)/g, "") as any
                      }
                      disabled
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Form.Item label={t("scrap_notes")} name="scrap_rubber_notes">
                <Input.TextArea rows={2} placeholder={t("enter_notes")} />
              </Form.Item>
            </Col>
          </Row>

          <Card
            title={t("linked_purchase_tickets_title")}
            size="small"
            style={{
              marginTop: 16,
              marginBottom: 24,
              border: "1px dashed var(--ant-color-border)",
            }}>
            <Flex style={{ marginBottom: 16 }} gap={4}>
              <Input.Search
                placeholder={t("search_ticket_placeholder")}
                onSearch={handleSearchPurchase}
                allowClear
                style={{ width: 300 }}
              />
              <RangePicker
                placeholder={[tCommon("from_date"), tCommon("to_date")]}
                onChange={handleDateChangePurchase}
                presets={rangePresets}
                format="DD/MM/YYYY"
              />
            </Flex>

            <Table
              rowKey="transaction_ticket_id"
              rowSelection={{
                selectedRowKeys: selectedPurchaseKeys,
                onChange: (newSelectedRowKeys) => {
                  setSelectedPurchaseKeys(newSelectedRowKeys);
                  form.setFieldsValue({
                    purchase_ticket_ids: newSelectedRowKeys,
                  });
                },
              }}
              columns={purchaseColumns}
              dataSource={purchaseTicketsData?.data?.records || []}
              loading={isFetchingPurchaseTickets}
              pagination={{
                current: purchaseTicketsData?.data?.current_page || 1,
                pageSize: purchaseTicketsData?.data?.page_limit || 10,
                total: purchaseTicketsData?.data?.total_records || 0,
                onChange: (page, limit) =>
                  setPurchaseParams((prev) => ({ ...prev, page, limit })),
              }}
              scroll={{ x: "max-content" }}
            />

            <Form.Item
              name="purchase_ticket_ids"
              rules={[
                {
                  required: true,
                  message: t("select_at_least_one_ticket"),
                },
              ]}
              noStyle>
              <Input type="hidden" />
            </Form.Item>

            <Form.Item name="buyer_user_id" noStyle>
              <Input type="hidden" />
            </Form.Item>

            <Form.Item name="buyer_account_type" noStyle>
              <Input type="hidden" />
            </Form.Item>

            <Form.Item name="seller_user_id" noStyle>
              <Input type="hidden" />
            </Form.Item>

            <Form.Item name="seller_account_type" noStyle>
              <Input type="hidden" />
            </Form.Item>
          </Card>

          <Divider />
          <Flex justify="flex-end" gap="small">
            <Button onClick={onBack}>{tCommon("cancel")}</Button>
            <Button type="primary" htmlType="submit">
              {sale ? t("sale.edit_title") : t("sale.add_title")}
            </Button>
          </Flex>
        </Form>
      </Card>

      <ModalLinkedUser
        open={isModalOpen}
        onClose={() => {
          setIsModalOpen(false);
          setActiveLinkType(null);
        }}
        onSelect={handleSelectLinkedUser}
      />
    </Spin>
  );
};

export default SaleForm;
