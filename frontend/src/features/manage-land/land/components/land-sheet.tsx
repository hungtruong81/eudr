import BaseSheet from "@/components/shared/base-sheet";
import { SOIL_OPTIONS } from "@/constants/data_field";
import { getProvince, getZone, uploadFile } from "@/lib/api";
import {
  InboxOutlined,
  MinusCircleOutlined,
  PlusOutlined,
  UploadOutlined,
} from "@ant-design/icons";
import { DrawingManager, GoogleMap, Polygon } from "@react-google-maps/api";
import { useQuery } from "@tanstack/react-query";
import {
  AutoComplete,
  Button,
  Card,
  Col,
  Divider,
  Form,
  Input,
  InputNumber,
  message,
  Row,
  Select,
  Space,
  Spin,
  Upload,
} from "antd";
import type { UploadFile } from "antd/es/upload/interface";
import { useCallback, useEffect, useRef, useState } from "react";
import { createLand, updateLand } from "../actions";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";
import CustomFieldRenderer, {
  CustomFieldRendererRef,
} from "@/features/custom-field/components/custom-field-renderer";
import { SignaturePad, SignaturePadRef } from "@/components/ui/signature-pad";
import { useSetting } from "@/hooks/use-setting";

export type LandSheetProps = {
  open: boolean;
  onClose: () => void;
  record?: any | null;
  defaultFarmer?: {
    farmer_user_id: number;
    farmer_name: string;
  };
};

const libraries: ("drawing" | "geometry")[] = ["drawing"];
const MAP_CONTAINER_STYLE = { width: "100%", height: "400px" };
const DEFAULT_CENTER = { lat: 10.762622, lng: 106.660172 };

const calculatePolygonArea = (coords: { lat: number; lng: number }[]): number => {
  if (!coords || coords.length < 3) return 0;
  
  const R = 6378137; // Earth's radius in meters
  
  let sumLat = 0;
  for (const coord of coords) {
    sumLat += coord.lat;
  }
  const avgLatRad = (sumLat / coords.length) * Math.PI / 180;
  const cosLat = Math.cos(avgLatRad);
  
  const points = coords.map((c) => {
    const x = c.lng * (Math.PI / 180) * R * cosLat;
    const y = c.lat * (Math.PI / 180) * R;
    return { x, y };
  });
  
  let area = 0;
  const j = points.length;
  for (let i = 0; i < j; i++) {
    const p1 = points[i];
    const p2 = points[(i + 1) % j];
    area += p1.x * p2.y - p2.x * p1.y;
  }
  
  const areaInSqm = Math.abs(area / 2);
  return areaInSqm / 10000; // Return in hectares (ha)
};

const LandSheet = ({
  open,
  onClose,
  record,
  defaultFarmer,
}: LandSheetProps) => {
  const t = useTranslations("ManageLand.Land");
  const tCommon = useTranslations("Common");
  const tCustomField = useTranslations("Manage.CustomField");
  const customFieldRef = useRef<CustomFieldRendererRef>(null);
  const signatureRef = useRef<SignaturePadRef>(null);

  const [form] = Form.useForm();
  const { data: settingsData } = useSetting();
  const showSignature =
    settingsData?.data?.find(
      (s) => s.setting_code === "show_e_signature_box_land",
    )?.value === "1";

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [fileList, setFileList] = useState<UploadFile[]>([]);
  const [isUploadingOCR, setIsUploadingOCR] = useState(false);
  const [polygonPath, setPolygonPath] = useState<
    { lat: number; lng: number }[]
  >([]);
  const polygonRef = useRef<any>(null);

  const updateArea = (coords: { lat: number; lng: number }[]) => {
    if (coords && coords.length >= 3) {
      const area = calculatePolygonArea(coords);
      const roundedArea = parseFloat(area.toFixed(4));
      form.setFieldValue("land_area", roundedArea);
      if (!form.getFieldValue("area_24")) {
        form.setFieldValue("area_24", roundedArea);
      }
    }
  };

  const zoneId = Form.useWatch("zone_id", form);
  const { data: provinces } = useQuery({
    queryKey: ["provinces"],
    queryFn: () => getProvince(),
    enabled: open,
  });

  const { data: zoneData } = useQuery({
    queryKey: ["zones"],
    queryFn: () => getZone(),
    enabled: open,
  });

  useEffect(() => {
    if (open) {
      if (
        record?.land_records &&
        typeof record.land_records === "object" &&
        !Array.isArray(record.land_records)
      ) {
        const initialFiles: UploadFile[] = Object.entries(
          record.land_records,
        ).map(([id, url]) => {
          const urlString = url as string;
          const fileName = urlString.split("/").pop() || `document-${id}`;

          return {
            uid: id,
            name: fileName,
            status: "done",
            url: urlString,
          };
        });

        setFileList(initialFiles);
        form.setFieldValue("land_records", Object.keys(record.land_records));
      } else {
        setFileList([]);
        form.setFieldValue("land_records", []);
      }
    }
  }, [open, record, form]);

  const onPolygonComplete = useCallback(
    (polygon: any) => {
      const path = polygon.getPath();
      const newCoords = [];
      for (let i = 0; i < path.getLength(); i++) {
        newCoords.push({ lat: path.getAt(i).lat(), lng: path.getAt(i).lng() });
      }
      setPolygonPath(newCoords);
      form.setFieldValue("coordinates", newCoords);
      updateArea(newCoords);
      polygon.setMap(null);
    },
    [form],
  );

  const onEditPolygon = useCallback(() => {
    if (polygonRef.current) {
      const path = polygonRef.current.getPath();
      const updatedCoords = [];
      for (let i = 0; i < path.getLength(); i++) {
        updatedCoords.push({
          lat: path.getAt(i).lat(),
          lng: path.getAt(i).lng(),
        });
      }
      setPolygonPath(updatedCoords);
      form.setFieldValue("coordinates", updatedCoords);
      updateArea(updatedCoords);
    }
  }, [form]);

  const onCoordinatesValuesChange = (changedValues: any, allValues: any) => {
    if (changedValues.coordinates) {
      const validPath = (allValues.coordinates || [])
        .filter(
          (c: any) =>
            c &&
            c.lat !== undefined &&
            c.lat !== null &&
            c.lng !== undefined &&
            c.lng !== null,
        )
        .map((c: any) => ({
          lat: Number(c.lat),
          lng: Number(c.lng),
        }))
        .filter((c: any) => !isNaN(c.lat) && !isNaN(c.lng));
      setPolygonPath(validPath);
      updateArea(validPath);
    }
  };

  const customUploadOCR = async (options: any) => {
    const { file, onSuccess, onError } = options;
    const selectedZone = form.getFieldValue("zone_id");

    if (!selectedZone) {
      message.error(t("select_zone_first"));
      onError("No zone selected");
      return;
    }

    try {
      setIsUploadingOCR(true);
      const formData = new FormData();
      formData.append("file", file);
      formData.append("detection", "true");
      formData.append("zone_id", selectedZone.toString());

      const res = await uploadFile(formData);

      if (res.data?.file?.file_id) {
        form.setFieldValue("land_document_detection", res.data.file.file_id);
      }

      if (res.data?.detection?.coordinates) {
        form.setFieldValue(
          "coordinate_origin_points",
          res.data.detection.coordinates,
        );

        const detectedCoords = res.data.detection.coordinates.map((c: any) => ({
          lat: c.x,
          lng: c.y,
        }));

        setPolygonPath(detectedCoords);
        form.setFieldValue("coordinates", detectedCoords);
        updateArea(detectedCoords);
      }

      message.success(t("ocr_success"));
      onSuccess("ok");
    } catch (error) {
      message.error(t("ocr_error"));
      onError(error);
    } finally {
      setIsUploadingOCR(false);
    }
  };

  const handleRemoveRecord = (file: UploadFile) => {
    setFileList((prev) => prev.filter((item) => item.uid !== file.uid));

    const currentRecordIds = form.getFieldValue("land_records") || [];
    const updatedRecordIds = currentRecordIds.filter(
      (id: string) => id !== file.uid,
    );
    form.setFieldValue("land_records", updatedRecordIds);
  };

  const handleFinish = async (values: any) => {
    try {
      setIsSubmitting(true);
      message.loading({
        content: t("saving_data"),
        key: "submit-form",
      });

      const filesToUpload = fileList.filter((f) => f.status !== "done");

      const uploadPromises = filesToUpload.map(async (file) => {
        const formData = new FormData();
        formData.append("file", (file.originFileObj as File) || (file as any));
        const res = await uploadFile(formData);
        return res.data?.file?.file_id;
      });

      const uploadedFileIds = await Promise.all(uploadPromises);
      const validNewIds = uploadedFileIds.filter((id) => id);

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

      const finalLandRecords = [...(values.land_records || []), ...validNewIds];

      const finalPayload = {
        ...values,
        land_records: finalLandRecords,
        signature_file_id: signatureFileId,
      };

      if (record) {
        await updateLand(finalPayload, record.plot_code);
      } else {
        await createLand(finalPayload);
      }

      message.success({
        content: record ? t("update_land_success") : t("create_land_success"),
        key: "submit-form",
      });
      handleClose();
    } catch (error: any) {
      handleApiError(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = useCallback(() => {
    form.resetFields();
    setPolygonPath([]);
    setFileList([]);
  }, [form]);

  useEffect(() => {
    if (open && record) {
      form.setFieldsValue({
        ...record,
      });

      if (record.coordinates && record.coordinates.length > 0) {
        setPolygonPath(record.coordinates);
      } else {
        setPolygonPath([]);
      }

      if (record.land_records && typeof record.land_records === "object") {
        const initialFiles = Object.entries(record.land_records).map(
          ([id, url]) => {
            const fileName =
              (url as string).split("/").pop() || `document-${id}`;
            return {
              uid: id,
              name: fileName,
              status: "done",
              url: url as string,
            };
          },
        );
        setFileList(initialFiles as UploadFile[]);
        form.setFieldValue("land_records", Object.keys(record.land_records));
      } else {
        setFileList([]);
        form.setFieldValue("land_records", []);
      }
    } else if (open && !record && defaultFarmer) {
      form.setFieldsValue({
        farmer_user_id: defaultFarmer.farmer_user_id,
        farmer_name: defaultFarmer.farmer_name,
      });
    } else if (!open) {
      handleClose();
    }
  }, [record, open, form, defaultFarmer, handleClose]);

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={record ? t("edit_land") : t("add_land_title")}
      width={1200}
      onOk={() => form.submit()}>
      <Spin spinning={isSubmitting} description={t("processing_data")}>
        <Form
          form={form}
          layout="vertical"
          onValuesChange={onCoordinatesValuesChange}
          onFinish={handleFinish}>
          <Form.Item name="land_document_detection" hidden>
            <Input />
          </Form.Item>
          <Form.Item name="coordinate_origin_points" hidden>
            <Input />
          </Form.Item>
          <Form.Item name="land_records" hidden>
            <Input />
          </Form.Item>

          <Row gutter={[24, 24]}>
            <Col span={12} xs={24} md={12}>
              <Row gutter={[16, 16]}>
                <Col span={12}>
                  <Form.Item
                    name="plot_name"
                    label={t("plot_name_label")}
                    rules={[
                      { required: true, message: t("plot_name_required") },
                    ]}>
                    <Input placeholder={t("plot_name_placeholder")} />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item name="farmer_user_id" hidden>
                    <Input />
                  </Form.Item>
                  <Form.Item
                    name="farmer_name"
                    label={t("farmer_name_label")}
                    rules={[
                      { required: true, message: t("farmer_name_required") },
                    ]}>
                    <Input
                      placeholder={t("farmer_name_placeholder")}
                      disabled={!!defaultFarmer}
                    />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item name="company_name" label={tCommon("company")}>
                    <Input placeholder={t("company_name_placeholder")} />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="ownership"
                    label={t("ownership_label")}
                    rules={[
                      { required: true, message: t("ownership_required") },
                    ]}>
                    <AutoComplete
                      placeholder={t("ownership_placeholder")}
                      showSearch={{
                        filterOption: (input: string, option: any) =>
                          option.label
                            .toLowerCase()
                            .includes(input.toLowerCase()),
                      }}
                      options={[
                        { value: "Owner", label: t("ownership_owner") },
                        { value: "Rent", label: t("ownership_rent") },
                      ]}
                    />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="province_id"
                    label={t("province_label")}
                    rules={[
                      { required: true, message: t("province_required") },
                    ]}>
                    <Select
                      showSearch={{
                        filterOption: (input, option) =>
                          (option?.label ?? "")
                            .toLowerCase()
                            .includes(input.toLowerCase()),
                      }}
                      options={provinces?.provinces?.map((item) => ({
                        value: item.province_id,
                        label: item.province_name,
                      }))}
                      placeholder={t("province_placeholder")}
                    />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="altitude_above_sea_level"
                    label={t("altitude_label")}>
                    <InputNumber style={{ width: "100%" }} placeholder="4.5" />
                  </Form.Item>
                </Col>
                <Col span={24}>
                  <Form.Item
                    name="address"
                    label={tCommon("address")}
                    rules={[
                      { required: true, message: t("address_required") },
                    ]}>
                    <Input.TextArea
                      rows={2}
                      placeholder={t("address_placeholder")}
                    />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="soil"
                    label={t("soil_label")}
                    rules={[{ required: true, message: t("soil_required") }]}>
                    <AutoComplete
                      options={SOIL_OPTIONS.map((item) => ({
                        value: item,
                        label: item,
                      }))}
                    />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="classify"
                    label={t("classify_label")}
                    rules={[
                      {
                        required: true,
                        message: t("classify_required"),
                      },
                    ]}>
                    <AutoComplete
                      options={[
                        { value: "K1", label: "K1" },
                        { value: "K2", label: "K2" },
                      ]}
                      placeholder={t("classify_placeholder")}
                    />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="land_area"
                    label={t("area_label")}
                    rules={[{ required: true, message: t("area_required") }]}>
                    <InputNumber style={{ width: "100%" }} min={0} />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="maximum_yield"
                    label={t("max_yield_label")}
                    rules={[
                      { required: true, message: t("max_yield_required") },
                    ]}>
                    <InputNumber style={{ width: "100%" }} min={0} />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="area_24"
                    label={t("area_24_label")}
                    rules={[
                      { required: true, message: t("area_24_required") },
                    ]}>
                    <InputNumber style={{ width: "100%" }} min={0} />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item
                    name="status"
                    label={t("usage_status_label")}
                    rules={[
                      { required: true, message: t("usage_status_required") },
                    ]}>
                    <Input />
                  </Form.Item>
                </Col>
                <Col span={24}>
                  <Form.Item name="notes" label={tCommon("notes")}>
                    <Input.TextArea rows={3} />
                  </Form.Item>
                </Col>

                <Col span={24}>
                  <Card size="small" title={t("land_records_label")}>
                    <Upload.Dragger
                      multiple
                      fileList={fileList}
                      beforeUpload={(file) => {
                        setFileList((prev) => [...prev, file]);
                        return false;
                      }}
                      onRemove={handleRemoveRecord}>
                      <p className="ant-upload-drag-icon">
                        <InboxOutlined />
                      </p>
                      <p className="ant-upload-text">{t("upload_hint")}</p>
                    </Upload.Dragger>
                  </Card>
                </Col>
              </Row>
            </Col>

            <Col span={12} xs={24} md={12}>
              <Row gutter={[16, 16]}>
                <Col span={24}>
                  <Card size="small" title={tCustomField("custom_fields")}>
                    <CustomFieldRenderer
                      ref={customFieldRef}
                      entityType="land"
                      namePrefix="custom_fields"
                      enabled={open}
                    />
                  </Card>
                </Col>
                <Col span={24}>
                  <Card size="small" title={t("ocr_title")}>
                    <Space style={{ width: "100%" }} align="baseline">
                      <Form.Item
                        name="zone_id"
                        noStyle
                        rules={[
                          {
                            required: false,
                            message: t("select_zone_required"),
                          },
                        ]}>
                        <Select
                          placeholder={t("select_zone_placeholder")}
                          style={{ width: 200 }}
                          options={zoneData?.zones?.map((z: any) => ({
                            value: z.zone_id,
                            label: `${z.zone_name} (${z.value})`,
                          }))}
                        />
                      </Form.Item>

                      <Upload
                        customRequest={customUploadOCR}
                        disabled={!zoneId}
                        showUploadList={false}
                        accept=".jpg,.png,.jpeg">
                        <Button
                          icon={<UploadOutlined />}
                          loading={isUploadingOCR}>
                          {t("ocr_button")}
                        </Button>
                      </Upload>
                    </Space>
                  </Card>
                </Col>

                <Col span={24}>
                  <Card size="small" title={t("map_title")}>
                    <GoogleMap
                      mapContainerStyle={MAP_CONTAINER_STYLE}
                      center={
                        polygonPath.length > 0 ? polygonPath[0] : DEFAULT_CENTER
                      }
                      zoom={12}
                      options={{
                        mapTypeId: "satellite",
                      }}>
                      {polygonPath.length === 0 && (
                        <DrawingManager
                          onPolygonComplete={onPolygonComplete}
                          options={{
                            drawingControl: true,
                            drawingControlOptions: {
                              drawingModes: [
                                google.maps.drawing.OverlayType.POLYGON,
                              ],
                            },
                          }}
                        />
                      )}

                      {polygonPath.length > 0 && (
                        <Polygon
                          path={polygonPath}
                          editable={true}
                          draggable={true}
                          onLoad={(polygon) => {
                            polygonRef.current = polygon;
                          }}
                          onMouseUp={onEditPolygon}
                          onDragEnd={onEditPolygon}
                        />
                      )}
                    </GoogleMap>

                    {polygonPath.length > 0 && (
                      <Button
                        danger
                        style={{ marginTop: 12 }}
                        onClick={() => {
                          setPolygonPath([]);
                          form.setFieldValue("coordinates", []);
                          form.setFieldValue("coordinate_origin_points", []);
                        }}>
                        {t("delete_drawing")}
                      </Button>
                    )}
                  </Card>
                </Col>

                {/* 3. Danh sách Input thay đổi toạ độ thủ công */}
                <Col span={24}>
                  <Card size="small" title={t("manual_coords_title")}>
                    <Form.List name="coordinates">
                      {(fields, { add, remove }) => (
                        <>
                          {fields.map(({ key, name, ...restField }, index) => (
                            <Space
                              key={key}
                              style={{ display: "flex", marginBottom: 8 }}
                              align="baseline">
                              <Form.Item
                                {...restField}
                                name={[name, "lat"]}
                                label={index === 0 ? t("latitude_label") : ""}
                                rules={[
                                  {
                                    required: true,
                                    message: t("lat_required"),
                                  },
                                ]}>
                                <InputNumber
                                  placeholder={t("latitude_label")}
                                  style={{ width: 140 }}
                                />
                              </Form.Item>
                              <Form.Item
                                {...restField}
                                name={[name, "lng"]}
                                label={index === 0 ? t("longitude_label") : ""}
                                rules={[
                                  {
                                    required: true,
                                    message: t("lng_required"),
                                  },
                                ]}>
                                <InputNumber
                                  placeholder={t("longitude_label")}
                                  style={{ width: 140 }}
                                />
                              </Form.Item>
                              <MinusCircleOutlined
                                onClick={() => remove(name)}
                                style={{ color: "red", cursor: "pointer" }}
                              />
                            </Space>
                          ))}
                          <Form.Item>
                            <Button
                              type="dashed"
                              onClick={() => add()}
                              block
                              icon={<PlusOutlined />}>
                              {t("add_coord_point")}
                            </Button>
                          </Form.Item>
                        </>
                      )}
                    </Form.List>
                  </Card>
                </Col>
              </Row>
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
      </Spin>
    </BaseSheet>
  );
};

export default LandSheet;
