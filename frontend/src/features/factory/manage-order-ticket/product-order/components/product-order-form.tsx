"use client";
import React, { useEffect, useState } from "react";
import {
  Form,
  Input,
  InputNumber,
  Select,
  DatePicker,
  Row,
  Col,
  message,
} from "antd";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getContracts } from "@/lib/api";
import { IContract } from "@/lib/types";
import { getProductTypes } from "../../../factory-metadata/product-type/action";
import {
  createProductionOrder,
  generateCodeProduction,
  updateProductionOrder,
} from "../actions";
import { IProductionOrder } from "../types";
import dayjs from "dayjs";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface IProductOrderFormProps {
  open: boolean;
  onClose: () => void;
  order: IProductionOrder | null;
  onSuccess: () => void;
}

const ProductOrderForm = ({
  open,
  onClose,
  order,
  onSuccess,
}: IProductOrderFormProps) => {
  const t = useTranslations("Factory.product_order");
  const tc = useTranslations("Common");
  
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const category = Form.useWatch("product_type_category", form);

  useEffect(() => {
    if (open) {
      if (order) {
        form.setFieldsValue({
          ...order,
          production_date: dayjs(order.production_date),
          contract_code: order.contract_code,
          product_type_id: String(order.product_type_id),
        });
      } else {
        form.resetFields();
        fetchCode();
      }
    }
  }, [open, order, form]);

  const fetchCode = async () => {
    try {
      const res = await generateCodeProduction();
      if (res?.data?.production_order_code || res?.production_order_code) {
        form.setFieldValue(
          "production_order_code",
          res.data?.production_order_code || res.production_order_code,
        );
      }
    } catch (error) {
      console.error("Lỗi khi lấy mã sản xuất:", error);
    }
  };

  const handleFinish = async (values: any) => {
    try {
      setLoading(true);
      const payload = {
        ...values,
        production_date: values.production_date.format("YYYY-MM-DD"),
        product_type_id: Number(values.product_type_id),
        required_quantity: Number(values.required_quantity),
      };

      if (order) {
        await updateProductionOrder(order.production_order_code, payload);
        message.success(t("update_success"));
      } else {
        await createProductionOrder(payload);
        message.success(t("create_success"));
      }
      onSuccess();
      onClose();
    } catch (error) {
      handleApiError(error);
    } finally {
      setLoading(false);
    }
  };

  const handleContractChange = (val: any, option: any) => {
    form.setFieldValue("contract_id", option?.contract_id || 0);
    form.setFieldValue("contract_code", val);
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={form.submit}
      title={order ? t("edit_title") : t("add_title")}
      loading={loading}>
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              label={t("name")}
              name="production_order_name"
              rules={[{ required: true, message: t("enter_name") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label={t("code")}
              name="production_order_code"
              rules={[{ required: true, message: t("enter_code") }]}>
              <Input placeholder={tc("auto_generate")} disabled className="uppercase" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label={t("production_date")}
              name="production_date"
              rules={[{ required: true, message: tc("select_date") }]}>
              <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item label={t("related_contract")} name="contract_code">
              <InfiniteScrollSelect<IContract>
                queryKey={["contracts-select"]}
                fetchFn={getContracts}
                mapOption={(item: IContract) => ({
                  label: item.contract_code,
                  value: item.contract_code,
                })}
                placeholder={t("select_contract")}
                onChange={handleContractChange}
              />
            </Form.Item>
            <Form.Item name="contract_id" hidden>
              <Input />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label={t("product_category")}
              name="product_type_category"
              rules={[{ required: true, message: tc("select_category") }]}>
              <Select
                placeholder={tc("select_category")}
                options={[
                  { label: t("concentrated_latex_label"), value: "concentrated_latex" },
                  { label: tc("scrap_rubber"), value: "scrap_rubber" },
                ]}
                onChange={() => form.setFieldValue("product_type_id", null)}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label={t("product")}
              name="product_type_id"
              rules={[{ required: true, message: t("select_product") }]}>
              <InfiniteScrollSelect
                queryKey={["product-types-select", category]}
                fetchFn={(p) =>
                  getProductTypes({ ...p, product_type_category: category })
                }
                mapOption={(item: any) => ({
                  label: item.product_type_name,
                  value: String(item.product_type_id),
                })}
                placeholder={t("select_product")}
                disabled={!category}
              />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item
              label={t("required_quantity")}
              name="required_quantity"
              rules={[{ required: true, message: tc("enter_quantity") }]}>
              <InputNumber
                style={{ width: "100%" }}
                min={0}
                placeholder={tc("enter_quantity")}
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default ProductOrderForm;
