"use client";

import React from "react";
import { Col, DatePicker, Form, Input, Row, TimePicker, Typography } from "antd";
import { useTranslations } from "next-intl";

const { Title } = Typography;

const StepTransport = () => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");

  return (
    <div style={{ padding: "16px 0" }}>
      <Title level={5}>{t("vehicle_driver_info")}</Title>
      <Row gutter={16}>
        <Col xs={24} md={8}>
          <Form.Item
            label={tCommon("vehicle_license_plate")}
            name={["transport", "vehicle_license_plate"]}
            rules={[{ required: true, message: tCommon("vehicle_license_plate_required") || "Vui lòng nhập biển số xe!" }]}>
            <Input placeholder="Ví dụ: 51G-12345" />
          </Form.Item>
        </Col>
        <Col xs={24} md={8}>
          <Form.Item
            label={t("driver_name")}
            name={["transport", "driver_name"]}
            rules={[{ required: true, message: t("driver_name_required") }]}>
            <Input placeholder={t("driver_name_placeholder")} />
          </Form.Item>
        </Col>
        <Col xs={24} md={8}>
          <Form.Item
            label={t("driver_phone")}
            name={["transport", "driver_phone"]}
            rules={[{ required: true, message: tCommon("phone_required") || "Vui lòng nhập số điện thoại!" }]}>
            <Input placeholder={tCommon("phone_placeholder")} />
          </Form.Item>
        </Col>
      </Row>

      <Title level={5} style={{ marginTop: 24 }}>
        {tCommon("route_info") || "Thông tin lộ trình"}
      </Title>
      <Row gutter={16}>
        <Col xs={24} md={8}>
          <Form.Item
            label={t("transport_date")}
            name={["transport", "transport_date"]}
            rules={[{ required: true, message: t("transport_date_required") }]}>
            <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
          </Form.Item>
        </Col>
        <Col xs={24} md={8}>
          <Form.Item
            label={t("pickup_time")}
            name={["transport", "pickup_time"]}
            rules={[{ required: true, message: t("pickup_time_required") }]}>
            <TimePicker style={{ width: "100%" }} format="HH:mm" />
          </Form.Item>
        </Col>
        <Col xs={24} md={8}>
          <Form.Item
            label={t("delivery_time")}
            name={["transport", "delivery_time"]}
            rules={[{ required: true, message: t("delivery_time_required") }]}>
            <TimePicker style={{ width: "100%" }} format="HH:mm" />
          </Form.Item>
        </Col>
      </Row>

      <Row gutter={16}>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("pickup_location")}
            name={["transport", "pickup_location"]}
            rules={[{ required: true, message: t("pickup_location_required") }]}>
            <Input placeholder={t("pickup_location_placeholder")} />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            label={t("delivery_location")}
            name={["transport", "delivery_location"]}
            rules={[{ required: true, message: t("delivery_location_required") }]}>
            <Input placeholder={t("delivery_location_placeholder")} />
          </Form.Item>
        </Col>
      </Row>

      <Form.Item label={t("transport_notes")} name={["transport", "notes"]}>
        <Input.TextArea rows={3} placeholder={t("transport_notes_placeholder")} />
      </Form.Item>
    </div>
  );
};

export default StepTransport;
