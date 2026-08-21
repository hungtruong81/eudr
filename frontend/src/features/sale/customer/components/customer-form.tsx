import BaseSheet from "@/components/shared/base-sheet";
import { uploadFile } from "@/lib/api";
import { InboxOutlined } from "@ant-design/icons";
import { Col, Form, Input, Row, Select, Upload, message, Spin } from "antd";
import type { UploadFile } from "antd/es/upload/interface";
import React, { useEffect, useState } from "react";
import { generateCodeCustomer } from "../actions";
import { ICustomer, ICustomerData } from "../types";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface CustomerFormProps {
  open: boolean;
  onClose: () => void;
  record: ICustomer | null;
  onFinish: (values: ICustomerData) => Promise<void>;
  loading?: boolean;
}

const CustomerForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: CustomerFormProps) => {
  const t = useTranslations("Customer");
  const tc = useTranslations("Common");
  const [form] = Form.useForm();
  const [fileList, setFileList] = useState<UploadFile[]>([]);
  const [isUploading, setIsUploading] = useState(false);

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue(record);
        if (
          record.business_license_file_urls &&
          record.business_license_file_ids
        ) {
          const initialFiles: UploadFile[] =
            record.business_license_file_ids.map((id, index) => ({
              uid: String(id),
              name:
                record.business_license_file_urls[index].split("/").pop() ||
                `license-${id}`,
              status: "done",
              url: record.business_license_file_urls[index],
            }));
          setFileList(initialFiles);
        }
      } else {
        form.resetFields();
        setFileList([]);
        fetchGeneratedCode();
      }
    }
  }, [open, record, form]);

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateCodeCustomer();
      if (res?.data?.customer_code || res?.customer_code) {
        form.setFieldValue(
          "customer_code",
          res.data?.customer_code || res.customer_code,
        );
      }
    } catch (error) {
      console.error("Error fetching customer code:", error);
    }
  };

  const handleUploadFiles = async (files: UploadFile[]) => {
    const newFiles = files.filter((f) => f.status !== "done");
    if (newFiles.length === 0) return [];

    const uploadPromises = newFiles.map(async (file) => {
      const formData = new FormData();
      formData.append("file", file as any);
      const res = await uploadFile(formData);
      console.log(res);
      return res.data?.file?.file_id;
    });

    return (await Promise.all(uploadPromises)).filter((id) => id);
  };

  const handleSubmit = async (values: any) => {
    try {
      setIsUploading(true);
      const newFileIds = await handleUploadFiles(fileList);
      const existingFileIds = fileList
        .filter((f) => f.status === "done")
        .map((f) => Number(f.uid));

      const finalValues: ICustomerData = {
        ...values,
        business_license_file_ids: [...existingFileIds, ...newFileIds],
      };

      await onFinish(finalValues);
    } catch (error) {
      handleApiError(error);
    } finally {
      setIsUploading(false);
    }
  };

  const handleRemoveFile = (file: UploadFile) => {
    setFileList((prev) => prev.filter((item) => item.uid !== file.uid));
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("create_title")}
      loading={loading || isUploading}
      width={800}>
      <Spin spinning={isUploading} description={t("uploading")}>
        <Form form={form} layout="vertical" onFinish={handleSubmit}>
          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="customer_code"
                label={t("customer_code")}
                rules={[{ required: true, message: t("enter_code_error") }]}>
                <Input
                  placeholder={t("customer_code")}
                  className="uppercase"
                  disabled
                />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="customer_name"
                label={t("customer_name")}
                rules={[{ required: true, message: t("enter_name_error") }]}>
                <Input placeholder={t("enter_name_error")} />
              </Form.Item>
            </Col>
            <Col span={24}>
              <Form.Item
                name="customer_company_name"
                label={t("company_name")}
                rules={[{ required: true, message: t("enter_company_error") }]}>
                <Input placeholder={t("enter_company_error")} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="customer_email"
                label={tc("email")}
                rules={[
                  { type: "email", message: t("invalid_email_error") },
                  { required: true, message: t("enter_email_error") },
                ]}>
                <Input placeholder={t("enter_email_error")} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="customer_phone"
                label={tc("phone_number")}
                rules={[{ required: true, message: t("enter_phone_error") }]}>
                <Input placeholder={t("enter_phone_error")} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item name="tax_code" label={t("tax_code")}>
                <Input placeholder={t("tax_code")} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="customer_type"
                label={t("type")}
                rules={[{ required: true, message: t("select_type_error") }]}>
                <Select
                  placeholder={t("select_type")}
                  options={[
                    { label: t("individual"), value: "individual" },
                    { label: t("enterprise"), value: "enterprise" },
                  ]}
                />
              </Form.Item>
            </Col>
            <Col span={24}>
              <Form.Item name="billing_address" label={t("billing_address")}>
                <Input.TextArea rows={2} placeholder={t("billing_address")} />
              </Form.Item>
            </Col>
            <Col span={24}>
              <Form.Item name="shipping_address" label={t("shipping_address")}>
                <Input.TextArea rows={2} placeholder={t("shipping_address")} />
              </Form.Item>
            </Col>
            <Col span={24}>
              <Form.Item name="notes" label={tc("notes")}>
                <Input.TextArea rows={2} placeholder={tc("notes")} />
              </Form.Item>
            </Col>
            <Col span={24}>
              <Form.Item label={t("business_license")}>
                <Upload.Dragger
                  multiple
                  fileList={fileList}
                  beforeUpload={(file) => {
                    setFileList((prev) => [...prev, file]);
                    return false;
                  }}
                  onRemove={handleRemoveFile}>
                  <p className="ant-upload-drag-icon">
                    <InboxOutlined />
                  </p>
                  <p className="ant-upload-text">{t("upload_hint")}</p>
                </Upload.Dragger>
              </Form.Item>
            </Col>
          </Row>
        </Form>
      </Spin>
    </BaseSheet>
  );
};

export default CustomerForm;
