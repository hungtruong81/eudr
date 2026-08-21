import { CustomColumnTypeTable } from "@/components/custom-table";
import { rangePresets } from "@/constants/range-date";
import { IConnection } from "@/features/connect/manage-connect/types";
import {
  getLandByTransactionTicket,
  getLands,
  IGetLandParams,
} from "@/features/manage-land/land/actions";
import { IPlot } from "@/features/manage-land/land/types";
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
  Select,
  Space,
  Spin,
  Table,
  Typography,
} from "antd";
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

export interface IPurchaseFormProps {
  purchase?: ITransactionTicket | null;
  onBack: () => void;
  refetch: () => void;
}

import { useTranslations } from "next-intl";

const PurchaseForm = ({ refetch, purchase, onBack }: IPurchaseFormProps) => {
  const t = useTranslations("TransactionTicket");
  const tCommon = useTranslations("Common");
  const tManageLand = useTranslations("ManageLand");
  const [form] = Form.useForm();
  const { userInfo } = useUser();
  const { data: settingPriceData } = useSetting();

  const [isModalOpen, setIsModalOpen] = React.useState(false);
  const [activeLinkType, setActiveLinkType] = React.useState<
    "buyer" | "seller" | null
  >(null);

  const salesSource = Form.useWatch("sales_source", form);

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

  const [landParams, setLandParams] = React.useState<Partial<IGetLandParams>>({
    page: 1,
    limit: 10,
    is_approved: 1,
  });

  const { data: purchaseTicketsData, isFetching: isFetchingPurchaseTickets } =
    useQuery({
      queryKey: [
        "transaction-ticket-purchases-inline-purchase",
        purchaseParams,
      ],
      queryFn: () => getTransactionTikets(purchaseParams),
      enabled: salesSource === "ticket",
    });

  const { data: landsData, isFetching: isFetchingLands } = useQuery({
    queryKey: ["lands-inline-purchase", landParams],
    queryFn: () => getLands(landParams),
    enabled: salesSource === "land",
  });

  const { data: linkedPurchasesData } = useQuery({
    queryKey: [
      "linked-purchase-tickets-purchase",
      purchase?.transaction_ticket_code,
    ],
    queryFn: async () => {
      const url =
        generateBaseApiUrl() +
        `/v1/transaction-ticket/${purchase?.transaction_ticket_code}/purchase-ticket/?page=1&limit=100`;
      const res = await axiosInstance.get(url);
      return res.data;
    },
    enabled: !!purchase?.transaction_ticket_code && salesSource === "ticket",
  });

  const { data: linkedLandsData } = useQuery({
    queryKey: ["linked-lands-purchase", purchase?.transaction_ticket_code],
    queryFn: () =>
      getLandByTransactionTicket({
        transaction_ticket_code: purchase?.transaction_ticket_code!,
        page: 1,
        limit: 100,
      }),
    enabled: !!purchase?.transaction_ticket_code && salesSource === "land",
  });

  const [selectedRowKeys, setSelectedRowKeys] = React.useState<React.Key[]>([]);

  React.useEffect(() => {
    if (purchase) {
      if (purchase.latex_notes || purchase.scrap_rubber_notes) {
        // This is a naive check. Ideally we'd have sales_source in the ticket data.
      }
    }
  }, [purchase]);

  React.useEffect(() => {
    if (linkedPurchasesData?.data?.records?.length) {
      form.setFieldsValue({ sales_source: "ticket" });
    } else if (linkedLandsData?.data?.records?.length) {
      form.setFieldsValue({ sales_source: "land" });
    }
  }, [linkedPurchasesData, linkedLandsData, form]);

  React.useEffect(() => {
    if (linkedPurchasesData?.data?.records && salesSource === "ticket") {
      const linkedIds = linkedPurchasesData.data.records.map(
        (ticket: any) => ticket.transaction_ticket_id,
      );
      setSelectedRowKeys(linkedIds);
      form.setFieldsValue({
        purchase_ticket_ids: linkedIds,
      });
    }
  }, [linkedPurchasesData, form, salesSource]);

  React.useEffect(() => {
    if (linkedLandsData?.data?.records && salesSource === "land") {
      const linkedIds = linkedLandsData.data.records.map(
        (land: any) => land.plot_id,
      );
      setSelectedRowKeys(linkedIds);
      form.setFieldsValue({
        plot_ids: linkedIds,
      });
    }
  }, [linkedLandsData, form, salesSource]);

  // Reset selected keys when switching source
  const isInitialLoad = React.useRef(true);
  React.useEffect(() => {
    if (isInitialLoad.current) {
      isInitialLoad.current = false;
      return;
    }

    // Only reset if it's NOT a result of linked data loading
    if (purchase) {
      if (
        salesSource === "ticket" &&
        linkedPurchasesData?.data?.records?.length
      )
        return;
      if (salesSource === "land" && linkedLandsData?.data?.records?.length)
        return;
    }

    setSelectedRowKeys([]);
    form.setFieldsValue({
      purchase_ticket_ids: undefined,
      plot_ids: undefined,
    });
  }, [salesSource, form, purchase]);

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
    if (purchase) return;
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
  }, [settingPriceData, form, purchase]);

  React.useEffect(() => {
    if (purchase) {
      form.setFieldsValue(purchase);
    }
  }, [purchase, form]);

  const handleOpenModal = (type: "buyer" | "seller") => {
    setActiveLinkType(type);
    setIsModalOpen(true);
  };

  const handleSelectLinkedUser = (user: IConnection) => {
    const isReceived = user.connection_direction === "received";
    form.setFieldsValue({
      seller_user_id: isReceived ? user.requester_user_id : user.target_user_id,
      seller_name: user.full_name,
      seller_phone: user.phone,
      seller_account_type: user.register_type,
    });

    setIsModalOpen(false);
    setActiveLinkType(null);
  };

  const handleLinkSelf = () => {
    form.setFieldsValue({
      seller_user_id: userInfo?.user_id,
      seller_name: userInfo?.fullName,
      seller_phone: userInfo?.phone,
      seller_account_type: userInfo?.register_type,
    });
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

  const landColumns: CustomColumnTypeTable<IPlot>[] = [
    {
      title: t("plot_code"),
      dataIndex: "plot_code",
      render: (val) => val.toUpperCase(),
    },
    {
      title: t("plot_name"),
      dataIndex: "plot_name",
      key: "plot_name",
    },
    {
      title: t("region"),
      dataIndex: "province_name",
      key: "province_name",
    },
    {
      title: t("crop_type"),
      dataIndex: "crop_type",
      key: "crop_type",
    },
    {
      title: t("year_of_planting"),
      dataIndex: "year_of_planting",
      key: "year_of_planting",
    },
    {
      title: t("land_area"),
      dataIndex: "land_area",
      key: "land_area",
    },
    {
      title: t("owner"),
      dataIndex: "full_name",
      key: "full_name",
    },
  ];

  const [isSubmitting, setIsSubmitting] = React.useState(false);
  const handleSubmit = async (values: ITransactionTicketPayload) => {
    try {
      setIsSubmitting(true);
      if (purchase) {
        await updateTransactionTicket(purchase.transaction_ticket_code, {
          ...values,
          transaction_ticket_type: "purchase",
        });
      } else {
        await createTransactionTicket({
          ...values,
          transaction_ticket_type: "purchase",
        });
      }
      refetch();
      onBack();
      form.resetFields();
      message.success(
        purchase ? t("purchase.update_success") : t("purchase.create_success"),
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
            {purchase ? t("purchase.edit_title") : t("purchase.add_title")}
          </Typography.Title>
        }
        extra={
          <Space>
            <Button
              className="text-xs!"
              type="link"
              onClick={() => handleOpenModal("seller")}
              icon={<LinkOutlined />}
              disabled={!!purchase}>
              {t("link_seller")}
            </Button>
            <Divider orientation="vertical" />
            <Button
              className="text-xs!"
              type="link"
              onClick={() => handleLinkSelf()}
              icon={<UserOutlined />}
              disabled={!!purchase}>
              {t("link_self")}
            </Button>
          </Space>
        }>
        <Form
          form={form}
          onFinish={handleSubmit}
          layout="vertical"
          initialValues={{
            buyer_user_id: userInfo?.user_id,
            buyer_name: userInfo?.fullName,
            buyer_phone: userInfo?.phone,
            buyer_account_type: userInfo?.register_type,
            sales_source: "land",
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
                    rules={[{ required: true }]}>
                    <Input placeholder={t("enter_buyer_name")} disabled />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={tCommon("phone_number")}
                    name="buyer_phone"
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
                    rules={[
                      {
                        required: true,
                        message: t("enter_seller_name"),
                      },
                    ]}>
                    <Input placeholder={t("enter_seller_name")} disabled />
                  </Form.Item>
                </Col>
                <Col span={24} md={12}>
                  <Form.Item
                    label={tCommon("phone_number")}
                    name="seller_phone"
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
            title={
              <Flex align="center" gap={16}>
                <span>{t("sales_source")}</span>
                <Form.Item name="sales_source" noStyle>
                  <Select style={{ width: 140 }}>
                    <Select.Option value="land">{t("from_land")}</Select.Option>
                    <Select.Option value="ticket">
                      {t("from_ticket")}
                    </Select.Option>
                  </Select>
                </Form.Item>
              </Flex>
            }
            size="small"
            style={{
              marginTop: 16,
              marginBottom: 24,
              border: "1px dashed var(--ant-color-border)",
            }}>
            {salesSource === "ticket" ? (
              <>
                <Flex style={{ marginBottom: 16 }} gap={4}>
                  <Input.Search
                    placeholder={t("search_ticket_placeholder")}
                    onSearch={(val) =>
                      setPurchaseParams((prev) => ({
                        ...prev,
                        contract_code: val,
                        page: 1,
                      }))
                    }
                    allowClear
                    style={{ width: 300 }}
                  />
                  <RangePicker
                    placeholder={[tCommon("from_date"), tCommon("to_date")]}
                    onChange={(dates) => {
                      setPurchaseParams((prev) => ({
                        ...prev,
                        start_date: dates?.[0]?.format("YYYY-MM-DD") || "",
                        end_date: dates?.[1]?.format("YYYY-MM-DD") || "",
                        page: 1,
                      }));
                    }}
                    presets={rangePresets}
                    format="DD/MM/YYYY"
                  />
                </Flex>

                <Table
                  rowKey="transaction_ticket_id"
                  rowSelection={{
                    selectedRowKeys: selectedRowKeys,
                    onChange: (newSelectedRowKeys) => {
                      setSelectedRowKeys(newSelectedRowKeys);
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
              </>
            ) : (
              <>
                <Flex style={{ marginBottom: 16 }} gap={4}>
                  <Input.Search
                    placeholder={t("search_land_placeholder")}
                    onSearch={(val) =>
                      setLandParams((prev) => ({
                        ...prev,
                        search: val,
                        page: 1,
                      }))
                    }
                    allowClear
                    style={{ width: 300 }}
                  />
                </Flex>
                <Table
                  rowKey="plot_id"
                  rowSelection={{
                    selectedRowKeys: selectedRowKeys,
                    onChange: (newSelectedRowKeys) => {
                      setSelectedRowKeys(newSelectedRowKeys);
                      form.setFieldsValue({
                        plot_ids: newSelectedRowKeys,
                      });
                    },
                  }}
                  columns={landColumns}
                  dataSource={landsData?.data?.records || []}
                  loading={isFetchingLands}
                  pagination={{
                    current: landsData?.data?.current_page || 1,
                    pageSize: landsData?.data?.page_limit || 10,
                    total: landsData?.data?.total_records || 0,
                    onChange: (page, limit) =>
                      setLandParams((prev) => ({ ...prev, page, limit })),
                  }}
                  scroll={{ x: "max-content" }}
                />
              </>
            )}

            <Form.Item
              name="purchase_ticket_ids"
              rules={[
                {
                  required: salesSource === "ticket",
                  message: t("select_at_least_one_ticket"),
                },
              ]}
              noStyle>
              <Input type="hidden" />
            </Form.Item>

            <Form.Item
              name="plot_ids"
              rules={[
                {
                  required: salesSource === "land",
                  message: t("select_at_least_one_plot"),
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
              {purchase ? t("purchase.edit_title") : t("purchase.add_title")}
            </Button>
          </Flex>
        </Form>
        <ModalLinkedUser
          open={isModalOpen}
          onClose={() => {
            setIsModalOpen(false);
            setActiveLinkType(null);
          }}
          onSelect={handleSelectLinkedUser}
        />
      </Card>
    </Spin>
  );
};

export default PurchaseForm;
