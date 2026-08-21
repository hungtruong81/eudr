import BaseSheet from "@/components/shared/base-sheet";
import {
  Form,
  Input,
  Select,
  DatePicker,
  InputNumber,
  Button,
  Row,
  Col,
  message,
  Card,
  Divider,
} from "antd";
import React, { useCallback, useEffect, useRef } from "react";
import dayjs from "dayjs";
import { IPlant } from "../types";
import { createPlant, getCropTypes, updatePlant } from "../actions";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getLands } from "../../land/actions";
import { ILandData, IPlot } from "../../land/types";
import { useQuery } from "@tanstack/react-query";
import { handleApiError } from "@/lib/api-error";
import { useSetting } from "@/hooks/use-setting";
import { SignaturePad, SignaturePadRef } from "@/components/ui/signature-pad";
import { uploadFile } from "@/lib/api";

interface IPlantSheetProps {
  open: boolean;
  onClose: () => void;
  record?: IPlant | null;
  onRefresh?: () => void;
}

import { useTranslations } from "next-intl";
import CustomFieldRenderer, {
  CustomFieldRendererRef,
} from "@/features/custom-field/components/custom-field-renderer";

const PlantSheet = ({ open, onClose, record, onRefresh }: IPlantSheetProps) => {
  const t = useTranslations("ManageLand.Plant");
  const tCommon = useTranslations("Common");
  const tCustomField = useTranslations("Manage.CustomField");
  const [form] = Form.useForm();
  const customFieldRef = useRef<CustomFieldRendererRef>(null);
  const signatureRef = useRef<SignaturePadRef>(null);

  const { data: settingsData } = useSetting();
  const showSignature =
    settingsData?.data?.find((s) => s.setting_code === "show_e_signature_box_plant")
      ?.value === "1";
  const { data: cropTypes } = useQuery({
    queryKey: ["crop-types"],
    queryFn: getCropTypes,
    enabled: open,
  });

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue({
          ...record,
          date_end_of_planting: record.date_end_of_planting
            ? dayjs(record.date_end_of_planting)
            : undefined,
        });
      } else {
        form.resetFields();
      }
    }
  }, [open, record, form]);

  const onFinish = async (values: any) => {
    try {
      const payload = {
        ...values,
        date_end_of_planting: values.date_end_of_planting
          ? values.date_end_of_planting.format("YYYY-MM-DD")
          : null,
      };

      if (showSignature && signatureRef.current?.isEmpty()) {
        message.warning(tCommon("signature_required"));
        return;
      }

      let signatureFileId;
      if (showSignature) {
        const signatureBlob = await signatureRef.current?.getSignatureBlob();
        if (signatureBlob) {
          const formData = new FormData();
          formData.append("file", signatureBlob, "signature.png");
          const uploadRes = await uploadFile(formData);
          signatureFileId = uploadRes.data.file.file_id;
        }
      }

      const finalPayload = {
        ...payload,
        signature_file_id: signatureFileId,
      };

      if (record) {
        await updatePlant({ ...finalPayload, plant_code: record.plant_code });
        message.success(t("update_success"));
      } else {
        await createPlant(finalPayload);
        message.success(t("create_success"));
      }

      if (onRefresh) onRefresh();
      onClose();
    } catch (error) {
      handleApiError(error);
    }
  };

  const mapLandOptions = useCallback(
    (item: IPlot) => ({
      value: item.plot_code,
      label: item.plot_name,
    }),
    [],
  );

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={record ? t("edit_plant") : t("add_plant_title")}
      onOk={form.submit}>
      <Form form={form} onFinish={onFinish} layout="vertical">
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              name="plot_code"
              label={t("plot_code")}
              rules={[{ required: true, message: t("plot_required") }]}>
              <InfiniteScrollSelect<IPlot>
                queryKey={["lands-select"]}
                fetchFn={getLands}
                mapOption={mapLandOptions}
                allowClear
                placeholder={t("plot_placeholder")}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="crop_type"
              label={t("crop_type")}
              rules={[{ required: true, message: t("crop_required") }]}>
              <Select
                placeholder={t("crop_placeholder")}
                options={cropTypes?.data?.map((item) => ({
                  value: item.crop_type_name,
                  label: item.crop_type_name,
                }))}
                allowClear
                showSearch={{
                  filterOption: (input, option) =>
                    (option?.label ?? "")
                      .toLowerCase()
                      .includes(input.toLowerCase()),
                }}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item name="plantation_name" label={t("plantation_name")}>
              <Input placeholder="VD: Trồng Cao Su 03" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item name="plant_status" label={t("plant_status")}>
              <Select
                placeholder={tCommon("select_status")}
                options={[
                  {
                    value: "Nảy mầm / Germination",
                    label: t("status_options.germination"),
                  },
                  {
                    value: "Cây con / Seedling",
                    label: t("status_options.seedling"),
                  },
                  {
                    value: "Cây giống / Sapling",
                    label: t("status_options.sapling"),
                  },
                  {
                    value: "Cây trưởng thành / Juvenile",
                    label: t("status_options.juvenile"),
                  },
                  {
                    value: "Ra nhựa / Tapping",
                    label: t("status_options.tapping"),
                  },
                  {
                    value: "Lão hóa / Senescence",
                    label: t("status_options.senescence"),
                  },
                ]}
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="year_of_planting"
              label={t("year_of_planting")}
              rules={[
                { required: true, message: t("year_of_planting_required") },
              ]}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 2000" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="date_end_of_planting"
              label={t("date_end_of_planting")}>
              <DatePicker
                style={{ width: "100%" }}
                format={["DD/MM/YYYY", "YYYY-MM-DD"]}
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item name="expected_harvest" label={t("expected_harvest_kg")}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 902.5" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="type_of_plantation"
              label={t("type_of_plantation")}
              rules={[
                {
                  required: true,
                  message: t("type_required"),
                },
              ]}>
              <Select
                placeholder="Chọn loại hình"
                options={[
                  { value: "Độc canh", label: t("monoculture") },
                  { value: "Xen Canh", label: t("intercropping") },
                  { value: "Nông Lâm Kết hợp", label: t("agroforestry") },
                  { value: "Khác", label: t("other") },
                ]}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item name="planting_method" label={t("planting_method")}>
              <Select
                options={[
                  { label: "D2", value: "D2" },
                  { label: "D3", value: "D3" },
                  { label: "D4", value: "D4" },
                  { label: "D5", value: "D5" },
                  { label: "D6", value: "D6" },
                ]}
                placeholder={t("method_placeholder")}
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="planting_distance"
              label={t("planting_distance")}
              rules={[{ required: true, message: t("distance_required") }]}>
              <Input placeholder="VD: 3x63x72,5x7" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="year_of_start_tapping"
              label={t("year_of_start_tapping")}
              rules={[
                { required: true, message: t("start_tapping_required") },
              ]}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 2006" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="year_of_upward_tapping"
              label={t("year_of_upward_tapping")}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 2008" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item name="tapping_method" label={t("tapping_method")}>
              <Select
                placeholder={t("method_placeholder")}
                options={[
                  { label: "D2", value: "D2" },
                  { label: "D3", value: "D3" },
                  { label: "D4", value: "D4" },
                  { label: "D5", value: "D5" },
                  { label: "D6", value: "D6" },
                ]}
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="percentage_of_trees_meeting_perimeter_standards"
              label={t("percentage_trees_standard")}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 55.7" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="denity_of_tapping_tree"
              label={t("denity_tapping_tree")}>
              <InputNumber style={{ width: "100%" }} placeholder="cây/ha" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="clone_type_of_tree"
              label={t("clone_type_tree")}
              rules={[{ required: true, message: t("clone_required") }]}>
              <Input placeholder="Tên loại giống được trồng..." />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              name="effective_tree_density"
              label={t("effective_tree_density")}
              rules={[{ required: true, message: t("density_required") }]}>
              <InputNumber style={{ width: "100%" }} placeholder="cây/ha" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item name="annual_yield" label={t("annual_yield")}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 320" />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item name="production_24" label={t("production_24")}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 0.6" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="standard_deviation"
              label={t("standard_deviation")}>
              <InputNumber style={{ width: "100%" }} placeholder="VD: 0.2" />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Card size="small" title={tCustomField("custom_fields")}>
              <CustomFieldRenderer
                ref={customFieldRef}
                entityType="plant"
                namePrefix="custom_fields"
                enabled={open}
              />
            </Card>
          </Col>
        </Row>

        {showSignature && (
          <div style={{ marginTop: 24 }}>
            <Divider titlePlacement="left">
              {tCommon("electronic_signature")}
            </Divider>
            <SignaturePad ref={signatureRef} width={1150} />
          </div>
        )}
      </Form>
    </BaseSheet>
  );
};

export default PlantSheet;
