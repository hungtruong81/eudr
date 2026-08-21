import BaseSheet from "@/components/shared/base-sheet";
import { Col, Form, Input, InputNumber, Row, DatePicker } from "antd";
import React, { useEffect } from "react";
import { IGrade, IGradePriceData } from "../type";
import { useTranslations } from "next-intl";
import dayjs from "dayjs";

interface GradePriceFormProps {
  open: boolean;
  onClose: () => void;
  record: IGrade | null;
  onFinish: (values: IGradePriceData) => Promise<void>;
  loading?: boolean;
}

const GradePriceForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: GradePriceFormProps) => {
  const [form] = Form.useForm();
  const t = useTranslations("Manage.Grade");

  useEffect(() => {
    if (open) {
      form.resetFields();
      if (record) {
        form.setFieldsValue({
          grade_code: record.grade_code,
          domestic_price: record.current_domestic_price || null,
          international_price: record.current_international_price || null,
          effective_from: record.current_price_effective_from
            ? dayjs(record.current_price_effective_from)
            : null,
          effective_to: record.current_price_effective_to
            ? dayjs(record.current_price_effective_to)
            : null,
        });
      }
    }
  }, [open, record, form]);

  const handleSubmit = (values: any) => {
    const data: IGradePriceData = {
      domestic_price: values.domestic_price,
      international_price: values.international_price,
      effective_from: values.effective_from
        ? values.effective_from.format("YYYY-MM-DD")
        : "",
      effective_to: values.effective_to
        ? values.effective_to.format("YYYY-MM-DD")
        : "",
      note: values.note || "",
    };
    onFinish(data);
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={t("update_price_title")}
      loading={loading}
      width={600}>
      <Form form={form} layout="vertical" onFinish={handleSubmit}>
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              name="domestic_price"
              label={t("price_domestic")}
              rules={[
                { required: true, message: t("price_domestic_required") },
              ]}>
              <InputNumber
                style={{ width: "100%" }}
                formatter={(value) =>
                  `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
                parser={(value) =>
                  value?.replace(/\$\s?|(,*)/g, "") as unknown as number
                }
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="international_price"
              label={t("price_international")}
              rules={[
                { required: true, message: t("price_international_required") },
              ]}>
              <InputNumber
                style={{ width: "100%" }}
                formatter={(value) =>
                  `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
                parser={(value) =>
                  value?.replace(/\$\s?|(,*)/g, "") as unknown as number
                }
              />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item
              name="effective_from"
              label={t("effective_from")}
              rules={[
                { required: true, message: t("effective_from_required") },
              ]}>
              <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
            </Form.Item>
          </Col>
          {/* <Col span={12}>
            <Form.Item name="effective_to" label={t("effective_to")}>
              <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
            </Form.Item>
          </Col> */}
          <Col span={24}>
            <Form.Item name="note" label={t("note")}>
              <Input.TextArea rows={3} placeholder={t("note_placeholder")} />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default GradePriceForm;
