"use client";

import React from "react";
import {
  Col,
  DatePicker,
  Form,
  Input,
  InputNumber,
  Row,
  Select,
  Typography,
} from "antd";
import { useQuery } from "@tanstack/react-query";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import { useTranslations } from "next-intl";

const { Title } = Typography;
const { RangePicker } = DatePicker;

const StepGeneralInfo = () => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");
  const tAccount = useTranslations("Account");

  return (
    <div style={{ padding: "16px 0" }}>
      <Title level={5}>{t("supplier_info")}</Title>
      <Row gutter={16}>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("supplier_company_name")}
            name="supplier_company_name"
            rules={[
              { required: true, message: t("supplier_company_name_required") },
            ]}>
            <Input placeholder={t("supplier_company_name_placeholder")} />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("supplier_factory_name")}
            name="supplier_factory_name"
            rules={[{ required: true, message: t("supplier_factory_name_required") }]}>
            <Input placeholder={t("supplier_factory_name_placeholder")} />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            label={tCommon("phone_number")}
            name="supplier_phone"
            rules={[
              { required: true, message: tCommon("phone_required") || "Vui lòng nhập số điện thoại!" },
            ]}>
            <Input placeholder={tCommon("phone_placeholder")} />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            label={tCommon("address")}
            name="supplier_address"
            rules={[{ required: true, message: tCommon("address_required") || "Vui lòng nhập địa chỉ!" }]}>
            <Input placeholder={tCommon("address_placeholder")} />
          </Form.Item>
        </Col>
      </Row>

      <Title level={5} style={{ marginTop: 24 }}>
        {t("lot_info")}
      </Title>
      <Row gutter={16}>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("original_lot_code")}
            name="original_product_lot_code"
            rules={[{ required: true, message: t("original_lot_code_required") }]}>
            <Input placeholder="Ví dụ: LOT-ABC-123" />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("receiving_factory")}
            name="factory_id"
            rules={[{ required: true, message: tCommon("select_factory_error") }]}>
            <InfiniteScrollSelect<IFactory>
              queryKey={["factories"]}
              fetchFn={getFactory}
              mapOption={(item) => ({
                label: item.factory_name,
                value: String(item.factory_id),
              })}
              placeholder={tCommon("select_factory")}
            />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            label={tCommon("grade")}
            name="grade"
            rules={[{ required: true, message: tCommon("select_grade_error") || "Vui lòng chọn phân loại!" }]}>
            <Select
              placeholder={tCommon("select_grade")}
              options={[
                { label: "SVR 3L", value: "SVR 3L" },
                { label: "SVR 5", value: "SVR 5" },
                { label: "SVR 10", value: "SVR 10" },
                { label: "SVR 20", value: "SVR 20" },
                { label: "SVR CV50", value: "SVR CV50" },
                { label: "SVR CV60", value: "SVR CV60" },
                { label: "RSS 1", value: "RSS 1" },
                { label: "RSS 3", value: "RSS 3" },
              ]}
            />
          </Form.Item>
        </Col>
        <Col xs={24} md={6}>
          <Form.Item
            label={t("total_blocks")}
            name="total_blocks"
            rules={[{ required: true, message: t("total_blocks_required") }]}>
            <InputNumber style={{ width: "100%" }} min={1} />
          </Form.Item>
        </Col>
        <Col xs={24} md={6}>
          <Form.Item
            label={tCommon("total_weight") + " (kg)"}
            name="total_weight"
            rules={[{ required: true, message: tCommon("enter_weight_error") }]}>
            <InputNumber
              style={{ width: "100%" }}
              min={0}
              formatter={(value) =>
                `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
              }
            />
          </Form.Item>
        </Col>
      </Row>

      <Row gutter={16}>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("production_period")}
            name="production_date_range"
            rules={[{ required: true, message: t("production_period_required") }]}>
            <RangePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
          </Form.Item>
        </Col>
        <Col xs={24} md={6}>
          <Form.Item
            label={t("purchase_date")}
            name="purchase_date"
            rules={[{ required: true, message: t("purchase_date_required") }]}>
            <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
          </Form.Item>
        </Col>
        <Col xs={24} md={6}>
          <Form.Item
            label={t("purchase_amount") + " (VNĐ)"}
            name="purchase_amount"
            rules={[{ required: true, message: t("purchase_amount_required") }]}>
            <InputNumber
              style={{ width: "100%" }}
              min={0}
              formatter={(value) =>
                `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
              }
              prefix="₫"
            />
          </Form.Item>
        </Col>
      </Row>

      <Form.Item label={tCommon("notes")} name="notes">
        <Input.TextArea rows={3} placeholder={tCommon("notes_placeholder")} />
      </Form.Item>
    </div>
  );
};

export default StepGeneralInfo;
