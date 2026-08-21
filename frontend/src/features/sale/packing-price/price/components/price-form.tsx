import BaseSheet from "@/components/shared/base-sheet";
import { Col, Form, Input, InputNumber, Row, Select } from "antd";
import React, { useEffect } from "react";
import { IPrice, IPriceData } from "../types";
import { useTranslations } from "next-intl";

interface PriceFormProps {
  open: boolean;
  onClose: () => void;
  record: IPrice | null;
  onFinish: (values: IPriceData) => Promise<void>;
  loading?: boolean;
}

const PriceForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: PriceFormProps) => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const [form] = Form.useForm();

  const priceType = Form.useWatch("price_type", form);

  useEffect(() => {
    if (open) {
      if (record) {
        const isStandardType = ["pallet", "box"].includes(record.price_type);
        form.setFieldsValue({
          price_name: record.price_name,
          price_type: record.price_type,
          custom_price_type: isStandardType ? "" : record.price_type,
          domestic_price: record.domestic_price,
          international_price: record.international_price,
        });
      } else {
        form.resetFields();
      }
    }
  }, [open, record, form]);

  const handleFinish = async (values: any) => {
    const finalType =
      values.price_type === "other"
        ? values.custom_price_type
        : values.price_type;

    const formattedValues: IPriceData = {
      price_name: values.price_name,
      price_type: finalType || "other",
      domestic_price: String(values.domestic_price || 0),
      international_price: String(values.international_price || 0),
    };
    await onFinish(formattedValues);
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_price_title") : t("create_price_title")}
      loading={loading}
      width={600}>
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="price_name"
              label={t("price_name")}
              rules={[{ required: true, message: t("price_name_required") }]}>
              <Input placeholder={t("price_name")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item
              name="price_type"
              label={t("price_type")}
              rules={[{ required: true, message: t("price_type_required") }]}>
              {/* <Select
                placeholder={t("price_type")}
                options={[
                  { label: t("type_pallet"), value: "pallet" },
                  { label: t("type_box"), value: "box" },
                  { label: t("type_other"), value: "other" },
                ]}
              /> */}
              <Input placeholder={t("price_type")} />
            </Form.Item>
          </Col>

          {/* {priceType === "other" && (
            <Col span={24}>
              <Form.Item
                name="custom_price_type"
                label={t("type_other")}
                rules={[{ required: true, message: t("price_type_required") }]}>
                <Input placeholder={t("type_other")} />
              </Form.Item>
            </Col>
          )} */}

          <Col span={12}>
            <Form.Item
              name="domestic_price"
              label={t("domestic_price")}
              rules={[
                { required: true, message: t("domestic_price_required") },
              ]}>
              <InputNumber
                style={{ width: "100%" }}
                min={0}
                formatter={(value) =>
                  `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
                parser={(value) => value!.replace(/\$\s?|(,*)/g, "") as any}
                placeholder="0"
                suffix="VNĐ"
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="international_price"
              label={t("international_price")}
              rules={[
                { required: true, message: t("international_price_required") },
              ]}>
              <InputNumber
                style={{ width: "100%" }}
                min={0}
                formatter={(value) =>
                  `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
                parser={(value) => value!.replace(/\$\s?|(,*)/g, "") as any}
                placeholder="0"
                suffix="USD"
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default PriceForm;
