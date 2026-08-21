import BaseSheet from "@/components/shared/base-sheet";
import { Col, Form, Input, Row } from "antd";
import React, { useEffect } from "react";
import { IGrade, IGradeData } from "../type";
import { useTranslations } from "next-intl";

interface GradeFormProps {
  open: boolean;
  onClose: () => void;
  record: IGrade | null;
  onFinish: (values: IGradeData) => Promise<void>;
  loading?: boolean;
}

const GradeForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: GradeFormProps) => {
  const [form] = Form.useForm();
  const t = useTranslations("Manage.Grade");

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue(record);
      } else {
        form.resetFields();
      }
    }
  }, [open, record, form]);

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("create_title")}
      loading={loading}
      width={600}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="grade_code"
              label={t("grade_code")}
              rules={[{ required: true }]}>
              <Input disabled className="uppercase" />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item
              name="name"
              label={t("grade_name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("placeholder_name")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="description" label={t("description")}>
              <Input.TextArea
                rows={4}
                placeholder={t("placeholder_description")}
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default GradeForm;
