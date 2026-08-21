"use client";

import React, { useEffect } from "react";
import {
  Form,
  Card,
  InputNumber,
  Switch,
  Button,
  Space,
  Input,
  message,
  Spin,
  Divider,
  Typography,
} from "antd";
import { useTranslations } from "next-intl";
import { useSetting } from "@/hooks/use-setting";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { updateSettings } from "../actions";
import { handleApiError } from "@/lib/api-error";
import { SettingOutlined, SaveOutlined } from "@ant-design/icons";

const { Title, Text } = Typography;

const GeneralSettingsForm = () => {
  const t = useTranslations("Management.Settings");
  const tCommon = useTranslations("Common");
  const [form] = Form.useForm();
  const queryClient = useQueryClient();
  const { data: settingsData, isFetching } = useSetting();

  useEffect(() => {
    if (settingsData?.data) {
      const formValues: any = {};
      settingsData.data.forEach((item) => {
        if (item.setting_code.startsWith("show_e_signature_box_")) {
          formValues[item.setting_code] = item.value === "1";
        } else {
          formValues[item.setting_code] = Number(item.value);
        }
        formValues[`${item.setting_code}_comment`] = item.comment;
      });
      form.setFieldsValue(formValues);
    }
  }, [settingsData, form]);

  const updateMutation = useMutation({
    mutationFn: (values: any) => {
      const payload = (settingsData?.data || []).map((item) => {
        const val = values[item.setting_code];
        const comment = values[`${item.setting_code}_comment`];
        return {
          setting_code: item.setting_code,
          comment: comment || "",
          value: typeof val === "boolean" ? (val ? "1" : "0") : String(val),
        };
      });
      return updateSettings(payload);
    },
    onSuccess: () => {
      message.success(tCommon("update_success"));
      queryClient.invalidateQueries({ queryKey: ["setting"] });
    },
    onError: (error) => handleApiError(error),
  });

  const onFinish = (values: any) => {
    updateMutation.mutate(values);
  };

  if (isFetching && !settingsData) {
    return (
      <div className="flex justify-center items-center h-64">
        <Spin size="large" />
      </div>
    );
  }

  return (
    <Card
      title={
        <Space>
          <SettingOutlined />
          <span>{t("general_configuration")}</span>
        </Space>
      }
      className="shadow-md rounded-xl">
      <Form
        form={form}
        layout="vertical"
        onFinish={onFinish}
        requiredMark={false}>
        <Title level={5}>{t("price_settings")}</Title>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Space orientation="vertical" style={{ width: "100%" }}>
            <Form.Item
              name="latex_price_per_tsc_kg"
              label={t("latex_price_per_tsc_kg")}
              tooltip={t("latex_price_per_tsc_kg_tooltip")}
              className="mb-1">
              <InputNumber
                style={{ width: "100%" }}
                formatter={(value) =>
                  `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
                parser={(value) => value!.replace(/\$\s?|(,*)/g, "") as any}
              />
            </Form.Item>
            <Form.Item
              name="latex_price_per_tsc_kg_comment"
              label={t("comment")}>
              <Input placeholder={t("enter_comment")} />
            </Form.Item>
          </Space>

          <Space orientation="vertical" style={{ width: "100%" }}>
            <Form.Item
              name="scrap_rubber_price_per_drc_kg"
              label={t("scrap_rubber_price_per_drc_kg")}
              tooltip={t("scrap_rubber_price_per_drc_kg_tooltip")}
              className="mb-1">
              <InputNumber
                style={{ width: "100%" }}
                formatter={(value) =>
                  `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
                parser={(value) => value!.replace(/\$\s?|(,*)/g, "") as any}
              />
            </Form.Item>
            <Form.Item
              name="scrap_rubber_price_per_drc_kg_comment"
              label={t("comment")}>
              <Input placeholder={t("enter_comment")} />
            </Form.Item>
          </Space>
        </div>

        <Divider />

        <Title level={5}>{t("signature_settings")}</Title>
        <Space orientation="vertical" style={{ width: "100%" }} size="middle">
          <div className="flex flex-col bg-gray-50 p-4 rounded-lg dark:bg-gray-800/50">
            <div className="flex justify-between items-center mb-2">
              <div>
                <Text strong>{t("show_e_signature_box_land")}</Text>
                <br />
                <Text type="secondary" className="text-xs">
                  {t("show_e_signature_box_land_desc")}
                </Text>
              </div>
              <Form.Item
                name="show_e_signature_box_land"
                valuePropName="checked"
                noStyle>
                <Switch />
              </Form.Item>
            </div>
            <Form.Item
              name="show_e_signature_box_land_comment"
              className="mb-0">
              <Input size="small" placeholder={t("enter_comment")} />
            </Form.Item>
          </div>

          <div className="flex flex-col bg-gray-50 p-4 rounded-lg dark:bg-gray-800/50">
            <div className="flex justify-between items-center mb-2">
              <div>
                <Text strong>{t("show_e_signature_box_plant")}</Text>
                <br />
                <Text type="secondary" className="text-xs">
                  {t("show_e_signature_box_plant_desc")}
                </Text>
              </div>
              <Form.Item
                name="show_e_signature_box_plant"
                valuePropName="checked"
                noStyle>
                <Switch />
              </Form.Item>
            </div>
            <Form.Item
              name="show_e_signature_box_plant_comment"
              className="mb-0">
              <Input size="small" placeholder={t("enter_comment")} />
            </Form.Item>
          </div>

          <div className="flex flex-col bg-gray-50 p-4 rounded-lg dark:bg-gray-800/50">
            <div className="flex justify-between items-center mb-2">
              <div>
                <Text strong>
                  {t("show_e_signature_box_import_product_lot")}
                </Text>
                <br />
                <Text type="secondary" className="text-xs">
                  {t("show_e_signature_box_import_product_lot_desc")}
                </Text>
              </div>
              <Form.Item
                name="show_e_signature_box_import_product_lot"
                valuePropName="checked"
                noStyle>
                <Switch />
              </Form.Item>
            </div>
            <Form.Item
              name="show_e_signature_box_import_product_lot_comment"
              className="mb-0">
              <Input size="small" placeholder={t("enter_comment")} />
            </Form.Item>
          </div>
        </Space>

        <div className="mt-8 flex justify-end">
          <Button
            type="primary"
            icon={<SaveOutlined />}
            htmlType="submit"
            loading={updateMutation.isPending}
            size="large"
            className="min-w-[150px]">
            {tCommon("save")}
          </Button>
        </div>
      </Form>
    </Card>
  );
};

export default GeneralSettingsForm;
