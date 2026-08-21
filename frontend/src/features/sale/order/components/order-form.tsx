import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getCustomers } from "@/features/sale/customer/actions";
import { getRawMaterialTank } from "@/features/factory/factory-metadata/raw-material-tank/actions";
import { getProductTank } from "@/features/factory/factory-metadata/product-tank/actions";
import { getProductTypes } from "@/features/factory/factory-metadata/product-type/action";
import { getTransactionTikets } from "@/features/transaction-ticket/actions";
import { MinusCircleOutlined, PlusOutlined } from "@ant-design/icons";
import {
  Button,
  Col,
  Form,
  Input,
  InputNumber,
  Row,
  Select,
  Card,
  Divider,
  DatePicker,
} from "antd";
import React, { useEffect, useMemo, useState } from "react";
import { generateCode, getOrderByCode } from "../actions";
import { IOrder, IOrderData } from "../types";
import { ITransactionTicket } from "@/features/transaction-ticket/types";
import dayjs from "dayjs";
import { useQuery } from "@tanstack/react-query";
import { handleApiError } from "@/lib/api-error";
import { getConnections } from "@/features/connect/manage-connect/actions";
import { useInfiniteQuery } from "@tanstack/react-query";
import { getProductLots } from "@/features/factory/lot/actions";
import { IProductLot } from "@/features/factory/lot/types";
import { useUser } from "@/providers/user-context";
import ProductLotItemDetails from "./product-lot-item-details";
import { useTranslations } from "next-intl";

interface OrderFormProps {
  open: boolean;
  onClose: () => void;
  record: IOrder | null;
  onFinish: (values: IOrderData) => Promise<void>;
  loading?: boolean;
}

const ProductLotDetailWrapper = ({
  name,
  form,
  lotCodeMap,
}: {
  name: number;
  form: any;
  lotCodeMap: Map<string, string>;
}) => {
  const lotId = Form.useWatch(["items", name, "product_lot_id"], form);
  const lotCode = lotId ? lotCodeMap.get(String(lotId)) : undefined;

  return (
    <ProductLotItemDetails
      productLotCode={lotCode}
      parentFieldName={name}
      form={form}
    />
  );
};

const OrderForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: OrderFormProps) => {
  const ts = useTranslations("Sales");
  const tc = useTranslations("Common");
  const tr = useTranslations("Register");
  const [form] = Form.useForm();
  const sourceType = Form.useWatch("order_source_type", form);
  const items = Form.useWatch("items", form) || [];

  const { data: lotsData } = useInfiniteQuery({
    queryKey: ["product-lot-order-form", { search: "", limit: 100 }],
    queryFn: ({ pageParam = 1 }) =>
      getProductLots({
        search: "",
        page: pageParam,
        limit: 100,
      }),
    initialPageParam: 1,
    getNextPageParam: (lastPage, allPages) => {
      const total = lastPage?.data?.total_records ?? 0;
      const loaded = allPages.length * 100;
      return loaded < total ? allPages.length + 1 : undefined;
    },
    enabled: open && sourceType === "product_lot",
  });

  const lotCodeMap = useMemo(() => {
    const map = new Map<string, string>();
    lotsData?.pages.forEach((page) => {
      const records = Array.isArray(page?.data?.records)
        ? page?.data?.records
        : [];
      records.forEach((lot: IProductLot) => {
        map.set(String(lot.product_lot_id), lot.product_lot_code);
      });
    });
    return map;
  }, [lotsData]);
  const buyerType = Form.useWatch("buyer_type", form);
  const { userInfo } = useUser();
  const { data: order } = useQuery({
    queryKey: ["order", record?.sale_order_code],
    queryFn: () => getOrderByCode(record?.sale_order_code || ""),
    enabled: !!record?.sale_order_code,
  });

  useEffect(() => {
    if (open && order) {
      form.setFieldsValue({
        ...order.order,
        delivery_date: order.order.delivery_date
          ? dayjs(order.order.delivery_date)
          : undefined,
        items:
          order.order.items?.map((item) => ({
            ...item,
            transaction_ticket_id: item.transaction_ticket_id
              ? String(item.transaction_ticket_id)
              : undefined,
          })) || [],
      });
    } else {
      form.resetFields();
      fetchGeneratedCode();
    }
  }, [open, order, form]);

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateCode();
      if (res?.data?.sale_order_code || res?.sale_order_code) {
        form.setFieldValue(
          "sale_order_code",
          res.data?.sale_order_code || res.sale_order_code,
        );
      }
    } catch (error) {
      handleApiError(error);
    }
  };

  const handleSubmit = async (values: any) => {
    // Transform items to include source_type
    const items = values.items.map((item: any) => ({
      ...item,
      // source_type:
      //   sourceType === "warehouse" ? "finished_product" : "raw_material",

      // ...(sourceType === "transaction_ticket" && {
      //   transaction_ticket_id: item.transaction_ticket_id || 0,
      //   raw_material_tank_id: item.raw_material_tank_id || 0,
      //   product_tank_id: 0,
      //   product_type_id: 0,
      // }),

      transaction_ticket_id: item.transaction_ticket_id || 0,
      raw_material_tank_id: item.raw_material_tank_id || 0,
      product_tank_id: 0,
      product_type_id: 0,
    }));

    await onFinish({
      ...values,
      items,
    });
  };

  const getOtherUserId = (item: any, currentUserId: number) => {
    if (item.requester_user_id === currentUserId) {
      return item.target_user_id;
    }
    return item.requester_user_id;
  };
  console.log(form.getFieldsValue());
  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? ts("edit_order") : ts("add_order")}
      loading={loading}
      width={1000}>
      <Form form={form} layout="vertical" onFinish={handleSubmit}>
        <Row gutter={16}>
          <Col span={8}>
            <Form.Item
              name="sale_order_code"
              label={ts("order_code")}
              rules={[{ required: true, message: ts("enter_order_code") }]}>
              <Input
                placeholder={ts("order_code")}
                className="uppercase"
                disabled
              />
            </Form.Item>
          </Col>

          <Col span={8}>
            <Form.Item
              name="buyer_type"
              initialValue="sales"
              label={ts("buyer")}>
              <Select
                options={[
                  { label: tr("purchaser"), value: "sales" },
                  { label: tr("customers"), value: "customer" },
                ]}
              />
            </Form.Item>
          </Col>

          <Col span={8}>
            <Form.Item
              name="delivery_date"
              label={ts("order_date")}
              rules={[{ required: true, message: ts("select_date") }]}>
              <DatePicker
                format={["DD/MM/YYYY", "YYYY-MM-DD", "DDMMYYYY"]}
                style={{ width: "100%" }}
                placeholder={ts("select_date")}
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="order_source_type"
              label={ts("source")}
              initialValue="product_lot"
              rules={[{ required: true, message: ts("select_source") }]}>
              <Select
                options={[
                  // { label: "Kho", value: "warehouse" },
                  // { label: "Phiếu mua hàng", value: "transaction_ticket" },
                  { label: ts("product_lot"), value: "product_lot" },
                ]}
                onChange={() => form.setFieldValue("items", [{}])}
              />
            </Form.Item>
          </Col>
          {buyerType === "customer" ? (
            <Col span={8}>
              <Form.Item
                // name="customer_code"
                name="buyer_user_id"
                label={ts("customer")}
                rules={[{ required: true, message: ts("select_customer") }]}>
                <InfiniteScrollSelect
                  queryKey={["customers-form"]}
                  fetchFn={getCustomers}
                  mapOption={(item) => ({
                    label: `${item.customer_name} (${item.customer_phone})`,
                    value: item.customer_code,
                  })}
                  placeholder={ts("select_customer")}
                  initialOptions={
                    order?.order.customer_code
                      ? [
                          {
                            label: `${order.order.customer_name} (${order.order.customer_phone})`,
                            value: order.order.customer_code,
                          },
                        ]
                      : []
                  }
                />
              </Form.Item>
            </Col>
          ) : (
            <Col span={8}>
              <Form.Item
                name="buyer_user_id"
                // name="customer_code"
                label={ts("customer")}
                rules={[{ required: true, message: ts("select_customer") }]}>
                <InfiniteScrollSelect
                  queryKey={["connect-form-order"]}
                  fetchFn={(params) =>
                    getConnections({
                      ...params,
                      account_type: "purchaser",
                      type: "all",
                      status: "accepted",
                    })
                  }
                  mapOption={(item) => {
                    const otherUserId = getOtherUserId(
                      item,
                      userInfo?.user_id || 0,
                    );

                    return {
                      label: item.full_name,
                      value: String(otherUserId),
                      disabled: otherUserId === userInfo?.user_id,
                    };
                  }}
                  placeholder={ts("select_customer")}
                />
              </Form.Item>
            </Col>
          )}
          <Col span={8}>
            <Form.Item name="delivery_address" label={ts("delivery_address")}>
              <Input placeholder={ts("delivery_address")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="notes" label={ts("notes")}>
              <Input.TextArea rows={2} placeholder={ts("notes")} />
            </Form.Item>
          </Col>
        </Row>

        <Divider titlePlacement="left">{ts("product_list")}</Divider>

        <Form.List name="items">
          {(fields, { add, remove }) => (
            <>
              {fields.map(({ key, name, ...restField }) => (
                <Card
                  key={key}
                  size="small"
                  style={{ marginBottom: 16 }}
                  extra={
                    fields.length > 1 && (
                      <MinusCircleOutlined
                        onClick={() => remove(name)}
                        style={{ color: "red" }}
                      />
                    )
                  }>
                  <Row gutter={16}>
                    {sourceType !== "warehouse" && (
                      <>
                        <Col span={8}>
                          {/* <Form.Item
                            {...restField}
                            name={[name, "transaction_ticket_id"]}
                            label="Phiếu mua hàng"
                            rules={[{ required: true, message: "Chọn phiếu" }]}>
                            <InfiniteScrollSelect<ITransactionTicket>
                              queryKey={["purchase-tickets"]}
                              fetchFn={(p) =>
                                getTransactionTikets({
                                  ...p,
                                  status: "completed",
                                  transaction_ticket_type: "purchase",
                                })
                              }
                              mapOption={(item) => ({
                                label: `${item.contract_code} (${item.seller_name})`,
                                value: String(item.transaction_ticket_id),
                              })}
                              initialOptions={
                                order?.order.items?.[name]
                                  ?.transaction_ticket_id
                                  ? [
                                      {
                                        label: `${order.order.items[name].ticket_contract_code} (${order.order.items[name].ticket_seller_name})`,
                                        value: String(
                                          order.order.items[name]
                                            .transaction_ticket_id,
                                        ),
                                      },
                                    ]
                                  : []
                              }
                            />
                          </Form.Item> */}

                          <Form.Item
                            {...restField}
                            name={[name, "product_lot_id"]}
                            label={ts("product_lot")}
                            rules={[
                              { required: true, message: ts("select_source") },
                              ({ getFieldValue }) => ({
                                validator(_, value) {
                                  const currentItems =
                                    getFieldValue("items") || [];
                                  const duplicates = currentItems.filter(
                                    (it: any) =>
                                      it?.product_lot_id &&
                                      String(it.product_lot_id) ===
                                        String(value),
                                  );
                                  if (duplicates.length > 1) {
                                    return Promise.reject(
                                      new Error(ts("duplicate_lot")),
                                    );
                                  }
                                  return Promise.resolve();
                                },
                              }),
                            ]}>
                            <InfiniteScrollSelect<IProductLot>
                              queryKey={["product-lot-order-form"]}
                              fetchFn={getProductLots}
                              mapOption={(item) => {
                                const isSelectedElsewhere = items.some(
                                  (it: any, idx: number) =>
                                    idx !== name &&
                                    it?.product_lot_id &&
                                    String(it.product_lot_id) ===
                                      String(item.product_lot_id),
                                );
                                return {
                                  label: `${item?.product_lot_code?.toUpperCase()} (${item.total_weight})`,
                                  value: String(item.product_lot_id),
                                  disabled: isSelectedElsewhere,
                                };
                              }}
                            />
                          </Form.Item>
                        </Col>

                        {/* TẠM ẨN BỒN CHỨA
                        <Col span={8}>
                          <Form.Item
                            {...restField}
                            name={[name, "raw_material_tank_id"]}
                            label="Bồn chứa (Mủ tạp)"
                            rules={[{ required: true, message: "Chọn bồn" }]}>
                            <InfiniteScrollSelect
                              queryKey={["raw-material-tanks"]}
                              fetchFn={getRawMaterialTank}
                              mapOption={(item: any) => ({
                                label: item.raw_material_tank_name,
                                value: item.raw_material_tank_id,
                              })}
                              initialOptions={
                                order?.order.items?.[name]?.raw_material_tank_id
                                  ? [
                                      {
                                        label:
                                          order.order.items[name]
                                            .raw_material_tank_name,
                                        value:
                                          order.order.items[name]
                                            .raw_material_tank_id,
                                      },
                                    ]
                                  : []
                              }
                            />
                          </Form.Item>
                        </Col>
                        */}
                      </>
                    )}

                    {sourceType === "warehouse" && (
                      <>
                        <Col span={8}>
                          <Form.Item
                            {...restField}
                            name={[name, "product_tank_id"]}
                            label={ts("tank")}
                            rules={[
                              { required: true, message: ts("select_source") },
                            ]}>
                            <InfiniteScrollSelect
                              queryKey={["product-tanks"]}
                              fetchFn={getProductTank}
                              mapOption={(item: any) => ({
                                label: item.product_tank_name,
                                value: item.product_tank_id,
                              })}
                              initialOptions={
                                order?.order.items?.[name]?.product_tank_id
                                  ? [
                                      {
                                        label:
                                          order.order.items[name]
                                            .product_tank_name,
                                        value:
                                          order.order.items[name]
                                            .product_tank_id,
                                      },
                                    ]
                                  : []
                              }
                            />
                          </Form.Item>
                        </Col>
                        <Col span={8}>
                          <Form.Item
                            {...restField}
                            name={[name, "product_type_id"]}
                            label={ts("product")}
                            rules={[
                              { required: true, message: ts("select_source") },
                            ]}>
                            <InfiniteScrollSelect
                              queryKey={["product-types"]}
                              fetchFn={getProductTypes}
                              mapOption={(item: any) => ({
                                label: item.product_type_name,
                                value: item.product_type_id,
                              })}
                              initialOptions={
                                order?.order.items?.[name]?.product_type_id
                                  ? [
                                      {
                                        label:
                                          order.order.items[name]
                                            .product_type_name,
                                        value:
                                          order.order.items[name]
                                            .product_type_id,
                                      },
                                    ]
                                  : []
                              }
                            />
                          </Form.Item>
                        </Col>

                        <Col span={4}>
                          <Form.Item
                            {...restField}
                            name={[name, "qty_ordered"]}
                            label={ts("quantity")}
                            rules={[
                              { required: true, message: ts("enter_quantity") },
                            ]}>
                            <InputNumber style={{ width: "100%" }} min={0} />
                          </Form.Item>
                        </Col>

                        <Col span={4}>
                          <Form.Item
                            {...restField}
                            name={[name, "price"]}
                            label={ts("price")}
                            rules={[
                              { required: true, message: ts("enter_price") },
                            ]}>
                            <InputNumber
                              style={{ width: "100%" }}
                              min={0}
                              formatter={(value) =>
                                `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                              }
                              parser={(value) =>
                                (value
                                  ? String(value).replace(/,/g, "")
                                  : "") as any
                              }
                            />
                          </Form.Item>
                        </Col>
                      </>
                    )}

                    <Col span={8}>
                      <Form.Item
                        {...restField}
                        name={[name, "price"]}
                        label={
                          sourceType === "product_lot"
                            ? ts("total_amount")
                            : ts("price")
                        }
                        rules={[
                          { required: true, message: ts("enter_price") },
                        ]}>
                        <InputNumber
                          style={{ width: "100%" }}
                          min={0}
                          formatter={(value) =>
                            `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                          }
                          parser={(value) =>
                            (value
                              ? String(value).replace(/,/g, "")
                              : "") as any
                          }
                          disabled={sourceType === "product_lot"}
                        />
                      </Form.Item>
                    </Col>

                    <Col span={sourceType === "warehouse" ? 24 : 8}>
                      <Form.Item
                        {...restField}
                        name={[name, "notes"]}
                        label={ts("notes")}>
                        <Input placeholder={ts("notes")} />
                      </Form.Item>
                    </Col>
                  </Row>
                  <Col span={24}>
                    <ProductLotDetailWrapper
                      name={name}
                      form={form}
                      lotCodeMap={lotCodeMap}
                    />
                  </Col>
                </Card>
              ))}
              <Form.Item>
                <Button
                  type="dashed"
                  onClick={() => add()}
                  block
                  icon={<PlusOutlined />}>
                  {ts("add_product")}
                </Button>
              </Form.Item>
            </>
          )}
        </Form.List>
      </Form>
    </BaseSheet>
  );
};

export default OrderForm;
