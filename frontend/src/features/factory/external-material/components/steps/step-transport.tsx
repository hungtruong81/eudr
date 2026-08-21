"use client";
import {
  Col,
  DatePicker,
  Divider,
  Form,
  Input,
  Row,
  TimePicker,
} from "antd";
import React from "react";
import { useTranslations } from "next-intl";

const { TextArea } = Input;

const StepTransport = () => {
  const t = useTranslations("Factory.external_material");
  const tc = useTranslations("Common");

  return (
    <div style={{ padding: "16px 0" }}>
      <Row gutter={[24, 24]}>
        <Col xs={24} md={12}>
          <Divider titlePlacement="left" style={{ marginTop: 0 }}>
            {t("tab_transport")}
          </Divider>

          <Form.Item
            label={t("select_vehicle")}
            name={["transport", "vehicle_license_plate"]}
            rules={[{ required: true, message: tc("select_vehicle") }]}>
            <Input placeholder={t("vehicle_plate")} />
          </Form.Item>

          <Form.Item
            name={["transport", "driver_name"]}
            label={t("driver_name")}
            rules={[{ required: true, message: tc("enter_name") }]}>
            <Input placeholder={t("driver_name")} />
          </Form.Item>

          <Form.Item
            name={["transport", "driver_phone"]}
            label={t("driver_phone")}
            rules={[
              { required: true, message: tc("phone_required") },
            ]}>
            <Input placeholder={tc("phone_placeholder")} />
          </Form.Item>
        </Col>

        <Col xs={24} md={12}>
          <Divider titlePlacement="left" style={{ marginTop: 0 }}>
            {t("tab_transport")}
          </Divider>

          <Form.Item
            name={["transport", "pickup_location"]}
            label={t("pickup_location")}
            rules={[{ required: true, message: t("pickup_location_required") }]}>
            <Input placeholder={t("pickup_location")} />
          </Form.Item>

          <Form.Item
            name={["transport", "transport_date"]}
            label={t("transport_date")}
            rules={[{ required: true, message: t("time_required") }]}>
            <DatePicker
              style={{ width: "100%" }}
              format="DD/MM/YYYY"
              placeholder={tc("select_date")}
            />
          </Form.Item>

          <Row gutter={16}>
            <Col xs={12}>
              <Form.Item
                name={["transport", "pickup_time"]}
                label={t("pickup_time")}
                rules={[{ required: true, message: tc("select_time") }]}>
                <TimePicker
                  style={{ width: "100%" }}
                  format="HH:mm"
                  placeholder={tc("select_time")}
                />
              </Form.Item>
            </Col>
            <Col xs={12}>
              <Form.Item
                name={["transport", "delivery_time"]}
                label={t("delivery_time")}
                rules={[{ required: true, message: tc("select_time") }]}>
                <TimePicker
                  style={{ width: "100%" }}
                  format="HH:mm"
                  placeholder={tc("select_time")}
                />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item name={["transport", "notes"]} label={tc("notes")}>
            <TextArea rows={2} placeholder={tc("no_notes")} />
          </Form.Item>
        </Col>
      </Row>
    </div>
  );
};

export default StepTransport;
