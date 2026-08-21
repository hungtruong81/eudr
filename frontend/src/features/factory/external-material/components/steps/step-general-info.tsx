"use client";
import {
  Card,
  Col,
  DatePicker,
  Divider,
  Form,
  Input,
  InputNumber,
  Row,
} from "antd";
import React from "react";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import { useTranslations } from "next-intl";

const { TextArea } = Input;

const StepGeneralInfo = () => {
  const t = useTranslations("Factory.external_material");
  const tc = useTranslations("Common");

  return (
    <div style={{ padding: "16px 0" }}>
      <Row gutter={[24, 24]}>
        <Col xs={24} md={12}>
          <Divider titlePlacement="left" style={{ marginTop: 0 }}>
            {t("tab_general")}
          </Divider>

          <Form.Item
            name="factory_id"
            label={t("factory_receive")}
            rules={[{ required: true, message: t("select_factory_error") }]}>
            <InfiniteScrollSelect<IFactory>
              queryKey={["factories"]}
              fetchFn={getFactory}
              mapOption={(item) => ({
                label: item.factory_name,
                value: String(item.factory_id),
              })}
              placeholder={t("select_factory")}
            />
          </Form.Item>

          <Form.Item
            name="supplier_name"
            label={t("supplier_name")}
            rules={[{ required: true, message: tc("enter_name") }]}>
            <Input placeholder={t("supplier_name")} />
          </Form.Item>

          <Row gutter={16}>
            <Col span={24} md={12}>
              <Form.Item
                name="supplier_phone"
                label={t("supplier_phone")}
                rules={[
                  { required: true, message: tc("phone_required") },
                ]}>
                <Input placeholder={tc("phone_placeholder")} />
              </Form.Item>
            </Col>
            <Col span={24} md={12}>
              <Form.Item
                name="purchase_date"
                label={t("purchase_date")}
                rules={[
                  { required: true, message: t("time_required") },
                ]}>
                <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item
            name="supplier_address"
            label={t("supplier_address")}
            rules={[{ required: true, message: tc("address_required") }]}>
            <Input placeholder={tc("enter_address")} />
          </Form.Item>

          <Form.Item name="notes" label={t("order_notes")}>
            <TextArea rows={3} placeholder={tc("no_notes")} />
          </Form.Item>
        </Col>

        <Col xs={24} md={12}>
          <Divider titlePlacement="left" style={{ marginTop: 0 }}>
            {t("tab_general")}
          </Divider>

          <Card size="small" title={t("water_latex")} style={{ marginBottom: 16 }}>
            <Row gutter={16}>
              <Col span={12}>
                <Form.Item
                  name="latex_weight"
                  label={t("weight")}
                  rules={[{ required: true, message: t("yield_required") }]}>
                  <InputNumber
                    style={{ width: "100%" }}
                    min={0}
                    placeholder={t("weight")}
                    formatter={(value) =>
                      `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    }
                  />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="latex_tsc_grade"
                  label={t("tsc_grade")}
                  rules={[{ required: true, message: t("yield_required") }]}>
                  <InputNumber
                    style={{ width: "100%" }}
                    min={0}
                    max={100}
                    placeholder="%"
                  />
                </Form.Item>
              </Col>
            </Row>
          </Card>

          <Card size="small" title={t("scrap_rubber")} style={{ marginBottom: 16 }}>
            <Row gutter={16}>
              <Col span={12}>
                <Form.Item
                  name="scrap_rubber_weight"
                  label={t("weight")}
                  rules={[{ required: true, message: t("yield_required") }]}>
                  <InputNumber
                    style={{ width: "100%" }}
                    min={0}
                    placeholder={t("weight")}
                    formatter={(value) =>
                      `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    }
                  />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="scrap_rubber_drc_grade"
                  label={t("drc_grade")}
                  rules={[{ required: true, message: t("yield_required") }]}>
                  <InputNumber
                    style={{ width: "100%" }}
                    min={0}
                    max={100}
                    placeholder="%"
                  />
                </Form.Item>
              </Col>
            </Row>
          </Card>

          <Divider titlePlacement="left">{tc("total")}</Divider>
          <Form.Item
            name="total_amount"
            label={`${t("total_value")} (${t("currency_vnd")})`}
            rules={[{ required: true, message: t("yield_required") }]}>
            <InputNumber
              style={{ width: "100%" }}
              min={0}
              size="large"
              placeholder={t("total_value")}
              formatter={(value) =>
                `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
              }
              parser={(value) => value!.replace(/\$\s?|(,*)/g, "") as any}
            />
          </Form.Item>
        </Col>
      </Row>
    </div>
  );
};

export default StepGeneralInfo;
