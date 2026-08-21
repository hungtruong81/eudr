import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import BaseSheet from "@/components/shared/base-sheet";
import { getProductTank } from "@/features/factory/factory-metadata/product-tank/actions";
import { getRawMaterialTank } from "@/features/factory/factory-metadata/raw-material-tank/actions";
import { getVehicles } from "@/features/route/vehicle/actions";
import { IVehicle } from "@/features/route/vehicle/types";
import { getOrderByCode, getOrders } from "@/features/sale/order/actions";
import { getTransactionTikets } from "@/features/transaction-ticket/actions";
import { MinusCircleOutlined, PlusOutlined } from "@ant-design/icons";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Button,
  Card,
  Col,
  DatePicker,
  Divider,
  Form,
  Input,
  InputNumber,
  Row,
  Typography,
} from "antd";
import dayjs from "dayjs";
import React, { useEffect } from "react";
import { IOrder } from "../../order/types";
import { generateCode, getIssueByCode } from "../actions";
import { IIssue, IIssueData } from "../types";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IssueFormProps {
  open: boolean;
  onClose: () => void;
  record: IIssue | null;
  onFinish: (values: IIssueData) => Promise<void>;
  loading?: boolean;
}

const IssueForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: IssueFormProps) => {
  const t = useTranslations("Issue");
  const [form] = Form.useForm();
  const queryClient = useQueryClient();
  const [selectedOrderFull, setSelectedOrderFull] = React.useState<any>(null);

  const { data: issue } = useQuery({
    queryKey: ["issue-detail", record?.issue_code],
    queryFn: () => getIssueByCode(record?.issue_code!),
    enabled: !!record?.issue_code && open,
  });

  const { data: oderDetail } = useQuery({
    queryKey: ["order-detail", record?.sale_order_code],
    queryFn: () => getOrderByCode(record?.sale_order_code!),
    enabled: !!record?.sale_order_code && open,
  });

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue({
          ...record,
          sale_order_id: record.sale_order_id
            ? String(record.sale_order_id)
            : undefined,
          issue_date: record.issue_date ? dayjs(record.issue_date) : undefined,
        });
      } else {
        form.resetFields();
        form.setFieldValue("issue_date", dayjs());
        form.setFieldValue("items", [
          {
            allocations: [{}],
          },
        ]);
        fetchGeneratedCode();
      }
    }
  }, [open, record, form]);

  useEffect(() => {
    if (issue?.issue && open && record) {
      const i = issue.issue;
      form.setFieldsValue({
        ...i,
        sale_order_id: i.sale_order_id ? String(i.sale_order_id) : undefined,
        issue_date: i.issue_date ? dayjs(i.issue_date) : undefined,
        items: i.items?.map((item) => ({
          ...item,
          allocations:
            item.allocations && item.allocations.length > 0
              ? item.allocations.map((alloc) => ({
                  ...alloc,
                  product_tank_id: alloc.product_tank_id
                    ? String(alloc.product_tank_id)
                    : undefined,
                  raw_material_tank_id: alloc.raw_material_tank_id
                    ? String(alloc.raw_material_tank_id)
                    : undefined,
                  transaction_ticket_id: alloc.transaction_ticket_id
                    ? String(alloc.transaction_ticket_id)
                    : undefined,
                }))
              : [{}],
        })) || [
          {
            allocations: [{}],
          },
        ],
      });
    }
  }, [issue, open, form, record]);

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateCode();
      if (res?.data?.issue_code || res?.issue_code) {
        form.setFieldValue(
          "issue_code",
          res.data?.issue_code || res.issue_code,
        );
      }
    } catch (error) {
      console.error("Error fetching issue code:", error);
    }
  };

  const getProductTankOptions = (name: number, allocName: number) => {
    const issueAlloc = issue?.issue?.items?.[name]?.allocations?.[
      allocName
    ] as any;
    if (issueAlloc?.product_tank_id) {
      return [
        {
          label:
            issueAlloc.product_tank_name || `Bồn ${issueAlloc.product_tank_id}`,
          value: String(issueAlloc.product_tank_id),
        },
      ];
    }
    const orderItem =
      oderDetail?.order?.items?.[name] || selectedOrderFull?.items?.[name];
    if (orderItem?.product_tank_id) {
      return [
        {
          label: orderItem.product_tank_name,
          value: String(orderItem.product_tank_id),
        },
      ];
    }
    return [];
  };

  const getRawMaterialTankOptions = (name: number, allocName: number) => {
    const issueAlloc = issue?.issue?.items?.[name]?.allocations?.[
      allocName
    ] as any;
    if (issueAlloc?.raw_material_tank_id) {
      return [
        {
          label:
            issueAlloc.raw_material_tank_name ||
            `Bồn ${issueAlloc.raw_material_tank_id}`,
          value: String(issueAlloc.raw_material_tank_id),
        },
      ];
    }
    const orderItem =
      oderDetail?.order?.items?.[name] || selectedOrderFull?.items?.[name];
    if (orderItem?.raw_material_tank_id) {
      return [
        {
          label: orderItem.raw_material_tank_name,
          value: String(orderItem.raw_material_tank_id),
        },
      ];
    }
    return [];
  };

  const getTicketOptions = (name: number, allocName: number) => {
    const issueAlloc = issue?.issue?.items?.[name]?.allocations?.[
      allocName
    ] as any;
    if (issueAlloc?.transaction_ticket_id) {
      return [
        {
          label:
            issueAlloc.ticket_seller_name ||
            issueAlloc.transaction_ticket_code ||
            `${t("purchase_ticket")} ${issueAlloc.transaction_ticket_id}`,
          value: String(issueAlloc.transaction_ticket_id),
        },
      ];
    }
    const orderItem =
      oderDetail?.order?.items?.[name] || selectedOrderFull?.items?.[name];
    if (orderItem?.transaction_ticket_id) {
      return [
        {
          label:
            orderItem.ticket_seller_name || orderItem.transaction_ticket_code,
          value: String(orderItem.transaction_ticket_id),
        },
      ];
    }
    return [];
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("create_title")}
      loading={loading}
      width={1100}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={6}>
            <Form.Item
              name="issue_code"
              label={t("issue_code")}
              rules={[{ required: true, message: t("enter_code_error") }]}>
              <Input
                placeholder={t("issue_code")}
                className="uppercase"
                disabled
              />
            </Form.Item>
          </Col>
          <Col span={6}>
            <Form.Item
              name="sale_order_id"
              label={t("sale_order")}
              rules={[{ required: true, message: t("select_order_error") }]}>
              <InfiniteScrollSelect<IOrder>
                queryKey={["orders-form"]}
                fetchFn={getOrders}
                mapOption={(item) => ({
                  label: `${item.customer_name} (${item.customer_phone})`,
                  value: String(item.sale_order_id),
                  record: item,
                })}
                placeholder={t("select_order")}
                initialOptions={
                  record?.sale_order_id || issue?.issue?.sale_order_id
                    ? [
                        {
                          label:
                            record?.sale_order_code ||
                            issue?.issue?.sale_order_code ||
                            `${t("sale_order")} ${record?.sale_order_id || issue?.issue?.sale_order_id}`,
                          value: String(
                            record?.sale_order_id ||
                              issue?.issue?.sale_order_id,
                          ),
                        },
                      ]
                    : []
                }
                onSelect={async (value, option: any) => {
                  try {
                    const code = option?.record?.sale_order_code;
                    if (code) {
                      const res = await queryClient.fetchQuery({
                        queryKey: ["order-detail", code],
                        queryFn: () => getOrderByCode(code),
                      });
                      if (res?.order?.items) {
                        setSelectedOrderFull(res.order);
                        const newItems = res.order.items.map((i: any) => ({
                          sale_order_item_id: i.sale_order_item_id,
                          product_id: i.product_type_id,
                          uom: i.uom || "kg",
                          qty_issued: Math.max(
                            0,
                            i.qty_ordered - (i.qty_shipped || 0),
                          ),
                          price: i.price,
                          currency: i.currency || "VND",
                          allocations: [
                            {
                              product_tank_id: i.product_tank_id
                                ? String(i.product_tank_id)
                                : undefined,
                              raw_material_tank_id: i.raw_material_tank_id
                                ? String(i.raw_material_tank_id)
                                : undefined,
                              transaction_ticket_id: i.transaction_ticket_id
                                ? String(i.transaction_ticket_id)
                                : undefined,
                              qty_issued: Math.max(
                                0,
                                i.qty_ordered - (i.qty_shipped || 0),
                              ),
                            },
                          ],
                        }));
                        form.setFieldValue(
                          "items",
                          newItems.length > 0
                            ? newItems
                            : [{ allocations: [{}] }],
                        );
                      }
                    }
                  } catch (e) {
                    console.error("Error fetching order details:", e);
                  }
                }}
              />
            </Form.Item>
          </Col>
          <Col span={6}>
            <Form.Item
              name="issue_date"
              label={t("issue_date")}
              rules={[{ required: true, message: t("select_date_error") }]}>
              <DatePicker
                placeholder={t("select_issue_date")}
                format="DD/MM/YYYY"
                style={{ width: "100%" }}
              />
            </Form.Item>
          </Col>
          <Col span={6}>
            <Form.Item
              name="vehicle_no"
              label={t("vehicle_no")}
              rules={[{ required: true, message: t("select_vehicle_error") }]}>
              <InfiniteScrollSelect<IVehicle>
                queryKey={["vehicles-form"]}
                fetchFn={getVehicles}
                mapOption={(item) => ({
                  label: item.vehicle_name,
                  value: String(item.license_plate),
                })}
                placeholder={t("select_vehicle")}
                initialOptions={
                  record?.vehicle_no || issue?.issue?.vehicle_no
                    ? [
                        {
                          label: record?.vehicle_no || issue?.issue?.vehicle_no,
                          value: String(
                            record?.vehicle_no || issue?.issue?.vehicle_no,
                          ),
                        },
                      ]
                    : []
                }
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="shipper"
              label={t("shipper")}
              rules={[{ required: true, message: t("enter_shipper_error") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="receiver"
              label={t("receiver")}
              rules={[{ required: true, message: t("enter_receiver_error") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="notes" label={t("common_notes")}>
              <Input placeholder={t("issue_notes_placeholder")} />
            </Form.Item>
          </Col>
        </Row>

        <Divider titlePlacement="left">{t("items_title")}</Divider>

        <Form.List name="items">
          {(fields, { add, remove }) => (
            <>
              {fields.map(({ key, name, ...restField }) => (
                <Card
                  key={key}
                  size="small"
                  style={{ marginBottom: 24, border: "1px solid #d9d9d9" }}
                  title={
                    <Text strong>
                      {t("product_item")} #{name + 1}
                    </Text>
                  }
                  extra={
                    fields.length > 1 && (
                      <MinusCircleOutlined
                        onClick={() => remove(name)}
                        style={{ color: "red" }}
                      />
                    )
                  }>
                  <Row gutter={16}>
                    <Col span={6}>
                      <Form.Item
                        {...restField}
                        name={[name, "sale_order_item_id"]}
                        label={t("item_id")}
                        rules={[{ required: true, message: t("required") }]}>
                        <InputNumber
                          style={{ width: "100%" }}
                          placeholder="ID"
                        />
                      </Form.Item>
                    </Col>
                    <Col span={4}>
                      <Form.Item
                        {...restField}
                        name={[name, "product_id"]}
                        label={t("product_type_id")}
                        rules={[{ required: true, message: t("required") }]}>
                        <InputNumber
                          style={{ width: "100%" }}
                          placeholder="ID SP"
                        />
                      </Form.Item>
                    </Col>
                    <Col span={3}>
                      <Form.Item
                        {...restField}
                        name={[name, "uom"]}
                        label={t("uom")}
                        initialValue="kg">
                        <Input />
                      </Form.Item>
                    </Col>
                    <Col span={4}>
                      <Form.Item
                        {...restField}
                        name={[name, "qty_issued"]}
                        label={t("qty_issued")}
                        rules={[
                          { required: true, message: t("enter_qty_error") },
                        ]}>
                        <InputNumber style={{ width: "100%" }} min={0} />
                      </Form.Item>
                    </Col>
                    <Col span={4}>
                      <Form.Item
                        {...restField}
                        name={[name, "price"]}
                        label={t("unit_price")}>
                        <InputNumber style={{ width: "100%" }} min={0} />
                      </Form.Item>
                    </Col>
                    <Col span={3}>
                      <Form.Item
                        {...restField}
                        name={[name, "currency"]}
                        label={t("currency")}
                        initialValue="VND">
                        <Input />
                      </Form.Item>
                    </Col>
                  </Row>

                  <div
                    style={{
                      marginLeft: 32,
                      marginTop: 8,
                      padding: 12,
                      backgroundColor: "#fafafa",
                      borderRadius: 8,
                    }}>
                    <Text strong style={{ display: "block", marginBottom: 12 }}>
                      {t("allocation_title")}
                    </Text>
                    <Form.List name={[name, "allocations"]}>
                      {(
                        allocFields,
                        { add: addAlloc, remove: removeAlloc },
                      ) => (
                        <>
                          {allocFields.map(
                            ({
                              key: allocKey,
                              name: allocName,
                              ...restAllocField
                            }) => (
                              <Row
                                gutter={8}
                                key={allocKey}
                                align="bottom"
                                style={{ marginBottom: 8 }}>
                                <Col span={6}>
                                  <Form.Item
                                    {...restAllocField}
                                    name={[allocName, "product_tank_id"]}
                                    label={t("product_tank")}>
                                    <InfiniteScrollSelect
                                      queryKey={["product-tanks-alloc"]}
                                      fetchFn={getProductTank}
                                      mapOption={(item) => ({
                                        label: item.product_tank_name,
                                        value: String(item.product_tank_id),
                                      })}
                                      initialOptions={getProductTankOptions(
                                        name,
                                        allocName,
                                      )}
                                      placeholder={t("select_tank")}
                                    />
                                  </Form.Item>
                                </Col>
                                <Col span={6}>
                                  <Form.Item
                                    {...restAllocField}
                                    name={[allocName, "raw_material_tank_id"]}
                                    label={t("raw_material_tank")}>
                                    <InfiniteScrollSelect
                                      queryKey={["raw-material-tanks-alloc"]}
                                      fetchFn={getRawMaterialTank}
                                      mapOption={(item) => ({
                                        label: item.raw_material_tank_name,
                                        value: String(
                                          item.raw_material_tank_id,
                                        ),
                                      })}
                                      initialOptions={getRawMaterialTankOptions(
                                        name,
                                        allocName,
                                      )}
                                      placeholder={t("select_tank")}
                                    />
                                  </Form.Item>
                                </Col>
                                <Col span={6}>
                                  <Form.Item
                                    {...restAllocField}
                                    name={[allocName, "transaction_ticket_id"]}
                                    label={t("purchase_ticket")}>
                                    <InfiniteScrollSelect
                                      queryKey={["purchase-tickets-alloc"]}
                                      fetchFn={(p) =>
                                        getTransactionTikets({
                                          ...p,
                                          transaction_ticket_type: "purchase",
                                          status: "completed",
                                        })
                                      }
                                      mapOption={(item) => ({
                                        label: `${item.seller_name} - ${item.seller_phone}`,
                                        value: String(
                                          item.transaction_ticket_id,
                                        ),
                                      })}
                                      placeholder={t("select_voucher")}
                                      initialOptions={getTicketOptions(
                                        name,
                                        allocName,
                                      )}
                                    />
                                  </Form.Item>
                                </Col>
                                <Col span={4}>
                                  <Form.Item
                                    {...restAllocField}
                                    name={[allocName, "qty_issued"]}
                                    label={t("qty_allocated")}
                                    rules={[
                                      {
                                        required: true,
                                        message: t("enter_qty_error"),
                                      },
                                    ]}>
                                    <InputNumber
                                      style={{ width: "100%" }}
                                      min={0}
                                    />
                                  </Form.Item>
                                </Col>
                                <Col span={2}>
                                  {allocFields.length > 1 && (
                                    <Button
                                      type="text"
                                      danger
                                      icon={<MinusCircleOutlined />}
                                      onClick={() => removeAlloc(allocName)}
                                      style={{ marginBottom: 24 }}
                                    />
                                  )}
                                </Col>
                              </Row>
                            ),
                          )}
                          <Button
                            type="dashed"
                            onClick={() => addAlloc()}
                            block
                            icon={<PlusOutlined />}
                            size="small">
                            {t("add_allocation")}
                          </Button>
                        </>
                      )}
                    </Form.List>
                  </div>
                </Card>
              ))}
              <Button
                type="primary"
                onClick={() => add()}
                block
                icon={<PlusOutlined />}
                ghost
                style={{ marginBottom: 16 }}>
                {t("add_product")}
              </Button>
            </>
          )}
        </Form.List>
      </Form>
    </BaseSheet>
  );
};

export default IssueForm;
