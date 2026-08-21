"use client";

import React, { useState } from "react";
import dayjs from "dayjs";
import {
  Form,
  Modal,
  Upload,
  message,
  Button,
  Typography,
  Space,
  Input,
  Radio,
  DatePicker,
  Checkbox,
  InputNumber,
  Select,
} from "antd";
import {
  InboxOutlined,
  DownloadOutlined,
  UploadOutlined,
} from "@ant-design/icons";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { importExternalProductLot, importNonEudr } from "../actions";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";
import { useRef } from "react";
import AppModal from "@/components/modal";
import CustomFieldRenderer, {
  CustomFieldRendererRef,
} from "@/features/custom-field/components/custom-field-renderer";
import { uploadFile } from "@/lib/api";
import { SignaturePad, SignaturePadRef } from "@/components/ui/signature-pad";
import { useSetting } from "@/hooks/use-setting";

const { Dragger } = Upload;
const { Text, Link } = Typography;
const { RangePicker } = DatePicker;

interface Props {
  open: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

const ExternalProductLotImport = ({ open, onClose, onSuccess }: Props) => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");
  const tCustomField = useTranslations("Manage.CustomField");

  const [form] = Form.useForm();
  const [fileList, setFileList] = useState<any[]>([]);
  const [documentList, setDocumentList] = useState<any[]>([]);
  const signatureRef = useRef<SignaturePadRef>(null);
  const customFieldRef = useRef<CustomFieldRendererRef>(null);
  const [isEudr, setIsEudr] = useState<boolean>(true);
  const queryClient = useQueryClient();
  const { data: settingsData } = useSetting();
  const showSignature =
    settingsData?.data?.find(
      (s) => s.setting_code === "show_e_signature_box_import_product_lot",
    )?.value === "1";

  const validateMutation = useMutation({
    mutationFn: (values: any) => importExternalProductLot(values),
    onSuccess: () => {
      message.success(t("import_success"));
      queryClient.invalidateQueries({ queryKey: ["external-product-lots"] });
      onSuccess();
      onClose();
    },
    onError: (error) => handleApiError(error),
  });

  const importNonEudrMutation = useMutation({
    mutationFn: (values: any) => importNonEudr(values),
    onSuccess: async (response) => {
      const entityId = response?.data?.product_lot_id;
      if (entityId) {
        try {
          await customFieldRef.current?.saveValues(entityId);
        } catch (e) {
          console.warn("Custom field save failed:", e);
        }
      }
      message.success(t("import_success"));
      queryClient.invalidateQueries({ queryKey: ["external-product-lots"] });
      onSuccess();
      onClose();
      form.resetFields();
      setFileList([]);
      setDocumentList([]);
      signatureRef.current?.clear();
    },
    onError: (error) => handleApiError(error),
  });

  const handleImport = async () => {
    try {
      const values = await form.validateFields();

      if (isEudr && fileList.length === 0) {
        message.warning(t("select_excel_error"));
        return;
      }

      if (showSignature && signatureRef.current?.isEmpty()) {
        message.warning(t("signature_required"));
        return;
      }

      const signatureBlob = showSignature
        ? await signatureRef.current?.getSignatureBlob()
        : null;
      let signatureFileId;
      if (signatureBlob) {
        const formData = new FormData();
        formData.append("file", signatureBlob, "signature.png");
        const uploadRes = await uploadFile(formData);
        signatureFileId = uploadRes.data.file.file_id;
      }

      const documentFileIds = documentList
        .filter((f) => f.status === "done" && f.response?.data?.file?.file_id)
        .map((f) => f.response.data.file.file_id);

      if (isEudr) {
        validateMutation.mutate({
          file: fileList[0].originFileObj,
          supplier_company_name: values.supplier_company_name,
          is_eudr: true,
          // external_contract_code: values.external_contract_code,
          // external_system: values.external_system,
          // product_lot: values.product_lot,
          // quantity: values.quantity,
          // production_date: values.production_date
          //   ? values.production_date.format("YYYY-MM-DD")
          //   : undefined,
          // document_file_ids: documentFileIds,
          signature_file_id: signatureFileId,
        });
      } else {
        importNonEudrMutation.mutate({
          supplier_company_name: values.supplier_company_name,
          supplier_factory_name: values.supplier_factory_name,
          supplier_phone: values.supplier_phone,
          external_contract_code: values.external_contract_code,
          factory_id: values.factory_id,
          production_date_from:
            values.production_date_range?.[0]?.format("YYYY-MM-DD"),
          production_date_to:
            values.production_date_range?.[1]?.format("YYYY-MM-DD"),
          notes: values.notes,
          product_lots: values.product_lots || [],
          contract_file_ids: documentFileIds,
          signature_file_id: signatureFileId,
        });
      }
    } catch (e) {
      console.log("Validation failed:", e);
    }
  };

  return (
    <AppModal
      open={open}
      onCancel={onClose}
      title={t("import_title")}
      onOk={handleImport}
      confirmLoading={validateMutation.isPending}
      width={1200}
      okText={t("start_import")}
      cancelText={tCommon("cancel")}>
      <Form
        form={form}
        layout="vertical"
        initialValues={{ is_eudr: true }}
        onValuesChange={(changedValues) => {
          if (changedValues.is_eudr !== undefined) {
            setIsEudr(changedValues.is_eudr);
          }
        }}>
        <div style={{ marginBottom: 16 }}>
          <Space orientation="vertical" style={{ width: "100%" }}>
            <Text type="secondary">{t("import_desc")}</Text>
            {/* <Link href="/import_260407-NCJK0D9H_20260414.xlsx" download>
              <DownloadOutlined /> Tải file mẫu tại đây
            </Link> */}
          </Space>
        </div>

        <Form.Item name="is_eudr" label={t("lot_option")}>
          <Radio.Group>
            <Radio value={true}>{t("eudr")}</Radio>
            <Radio value={false}>{t("non_eudr")}</Radio>
          </Radio.Group>
        </Form.Item>

        <Form.Item
          label={t("supplier_company_name")}
          name="supplier_company_name"
          rules={[
            { required: true, message: t("supplier_company_name_required") },
          ]}>
          <Input placeholder={t("supplier_company_name_example")} />
        </Form.Item>

        {!isEudr && (
          <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
            <div style={{ display: "flex", gap: 16, flexWrap: "wrap" }}>
              <Form.Item
                style={{ flex: 1, minWidth: 200 }}
                label={t("supplier_factory_name")}
                name="supplier_factory_name"
                rules={[
                  {
                    required: true,
                    message: t("supplier_factory_name_required"),
                  },
                ]}>
                <Input placeholder={t("supplier_factory_name_placeholder")} />
              </Form.Item>
              <Form.Item
                style={{ flex: 1, minWidth: 150 }}
                label={tCommon("phone_number")}
                name="supplier_phone"
                rules={[
                  { required: true, message: tCommon("phone_required") },
                ]}>
                <Input placeholder={tCommon("phone_placeholder")} />
              </Form.Item>
            </div>

            <div style={{ display: "flex", gap: 16, flexWrap: "wrap" }}>
              <Form.Item
                style={{ flex: 1, minWidth: 150 }}
                label={t("external_contract_code")}
                name="external_contract_code"
                rules={[
                  {
                    required: true,
                    message: t("external_contract_code_required"),
                  },
                ]}>
                <Input placeholder={t("external_contract_code_placeholder")} />
              </Form.Item>
              <Form.Item
                style={{ flex: 1, minWidth: 200 }}
                label={t("receiving_factory")}
                name="factory_id"
                rules={[
                  { required: true, message: tCommon("select_factory_error") },
                ]}>
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
            </div>

            <div style={{ display: "flex", gap: 16, flexWrap: "wrap" }}>
              <Form.Item
                style={{ flex: 1, minWidth: 200 }}
                label={t("production_period")}
                name="production_date_range"
                rules={[
                  { required: true, message: t("production_period_required") },
                ]}>
                <RangePicker
                  style={{ width: "100%" }}
                  format="DD/MM/YYYY"
                  disabledDate={(current) =>
                    current && current.isAfter(dayjs(), "day")
                  }
                />
              </Form.Item>
            </div>

            <Form.Item label={tCommon("notes")} name="notes">
              <Input.TextArea
                rows={2}
                placeholder={tCommon("notes_placeholder")}
              />
            </Form.Item>
            <Typography.Title level={5}>
              {tCustomField("custom_fields")}
            </Typography.Title>

            <CustomFieldRenderer
              ref={customFieldRef}
              entityType="product_lot_import_none_eudr"
              namePrefix="custom_fields"
              enabled={!isEudr && open}
            />

            <Typography.Title level={5}>{t("items")}</Typography.Title>
            <Form.List name="product_lots">
              {(fields, { add, remove }) => (
                <div
                  style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                  {fields.map(({ key, name, ...restField }) => (
                    <div
                      key={key}
                      style={{
                        display: "flex",
                        gap: 8,
                        alignItems: "flex-end",
                      }}>
                      <Form.Item
                        {...restField}
                        name={[name, "product_lot_code"]}
                        label={t("original_lot_code_example")}
                        rules={[
                          { required: true, message: t("enter_item_name") },
                        ]}
                        style={{ flex: 2, marginBottom: 0 }}>
                        <Input placeholder={t("original_lot_code_example")} />
                      </Form.Item>
                      <Form.Item
                        {...restField}
                        name={[name, "quantity"]}
                        label={t("item_quantity")}
                        rules={[
                          {
                            required: true,
                            message: t("enter_quantity_short"),
                          },
                        ]}
                        style={{ flex: 1, marginBottom: 0 }}>
                        <InputNumber style={{ width: "100%" }} min={1} />
                      </Form.Item>
                      <Form.Item
                        {...restField}
                        name={[name, "unit"]}
                        label={t("unit")}
                        rules={[{ required: true, message: t("enter_unit") }]}
                        style={{ flex: 1, marginBottom: 0 }}>
                        <Input placeholder={t("unit_example")} />
                      </Form.Item>
                      <Form.Item
                        {...restField}
                        name={[name, "weight"]}
                        label={t("item_weight")}
                        rules={[
                          { required: true, message: t("enter_weight_short") },
                        ]}
                        style={{ flex: 1, marginBottom: 0 }}>
                        <InputNumber style={{ width: "100%" }} min={0} />
                      </Form.Item>
                      <Form.Item
                        {...restField}
                        name={[name, "notes"]}
                        label={t("item_notes")}
                        style={{ flex: 1, marginBottom: 0 }}>
                        <Input />
                      </Form.Item>
                      <Button type="dashed" danger onClick={() => remove(name)}>
                        {tCommon("delete")}
                      </Button>
                    </div>
                  ))}
                  <Button
                    type="dashed"
                    onClick={() => add()}
                    block
                    style={{ marginTop: 8 }}>
                    {t("add_item")}
                  </Button>
                </div>
              )}
            </Form.List>

            <Form.Item
              label={t("contract_documents")}
              style={{ marginTop: 16 }}>
              <Upload
                multiple
                accept=".pdf,.png,.jpg,.jpeg,.webp"
                fileList={documentList}
                onChange={({ fileList }) => setDocumentList(fileList)}
                customRequest={async ({ file, onSuccess, onError }) => {
                  try {
                    const formData = new FormData();
                    formData.append("file", file as Blob);
                    const res = await uploadFile(formData);
                    onSuccess?.(res);
                  } catch (e) {
                    onError?.(e as Error);
                  }
                }}>
                <Button icon={<UploadOutlined />}>
                  {t("upload_document")}
                </Button>
              </Upload>
            </Form.Item>
          </div>
        )}

        {isEudr && (
          <div
            style={{
              display: "flex",
              gap: 16,
              flexWrap: "wrap",
              marginTop: 16,
            }}>
            {/* <Form.Item
              style={{ flex: 1 }}
              label={t("external_contract_code")}
              name="external_contract_code"
              rules={[
                {
                  required: true,
                  message: t("external_contract_code_required"),
                },
              ]}>
              <Input placeholder={t("external_contract_code_placeholder")} />
            </Form.Item> */}

            {/* <Form.Item
              style={{ flex: 1, minWidth: 200 }}
              label={t("product_lot_code")}
              name="product_lot"
              rules={[
                { required: true, message: t("product_lot_code_required") },
              ]}>
              <Input placeholder={t("product_lot_code_placeholder")} />
            </Form.Item>

            <Form.Item
              style={{ flex: 1, minWidth: 150 }}
              label={t("quantity")}
              name="quantity"
              rules={[{ required: true, message: t("quantity_required") }]}>
              <InputNumber
                style={{ width: "100%" }}
                placeholder={t("quantity_placeholder")}
              />
            </Form.Item>

            <Form.Item
              style={{ flex: 1, minWidth: 200 }}
              label={t("production_date")}
              name="production_date"
              rules={[
                { required: true, message: t("production_date_required") },
              ]}>
              <DatePicker
                style={{ width: "100%" }}
                format="DD/MM/YYYY"
                placeholder={t("production_date_placeholder")}
              />
            </Form.Item> */}

            {/* <Form.Item
              label={t("garden_proof_documents")}
              style={{ width: "100%" }}>
              <Upload
                multiple
                accept=".pdf,.png,.jpg,.jpeg,.webp"
                fileList={documentList}
                onChange={({ fileList }) => setDocumentList(fileList)}
                customRequest={async ({ file, onSuccess, onError }) => {
                  try {
                    const formData = new FormData();
                    formData.append("file", file as Blob);
                    const res = await uploadFile(formData);
                    onSuccess?.(res);
                  } catch (e) {
                    onError?.(e as Error);
                  }
                }}>
                <Button icon={<UploadOutlined />}>
                  {t("upload_document")}
                </Button>
              </Upload>
            </Form.Item> */}
          </div>
        )}

        {isEudr && (
          <Form.Item label={t("excel_file")} style={{ marginTop: 16 }}>
            <Dragger
              maxCount={1}
              beforeUpload={(file) => {
                const isExcel =
                  file.type ===
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
                  file.name.endsWith(".xlsx");
                if (!isExcel) {
                  message.error(t("not_excel_error", { name: file.name }));
                }
                return false;
              }}
              onChange={({ fileList }) => setFileList(fileList)}
              fileList={fileList}>
              <p className="ant-upload-drag-icon">
                <InboxOutlined />
              </p>
              <p className="ant-upload-text">{tCommon("drag_drop_desc")}</p>
              <p className="ant-upload-hint">{tCommon("xlsx_only")}</p>
            </Dragger>
          </Form.Item>
        )}

        {showSignature && (
          <Form.Item label={t("electronic_signature")} required>
            <SignaturePad ref={signatureRef} width={1100} />
          </Form.Item>
        )}

        <Form.Item
          name="terms_accepted"
          valuePropName="checked"
          rules={[
            {
              validator: (_, value) =>
                value
                  ? Promise.resolve()
                  : Promise.reject(new Error(t("terms_required"))),
            },
          ]}>
          <Checkbox>{t("accept_terms")}</Checkbox>
        </Form.Item>
      </Form>
    </AppModal>
  );
};

export default ExternalProductLotImport;
