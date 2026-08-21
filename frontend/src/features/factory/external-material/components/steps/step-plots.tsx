"use client";
import {
  Button,
  Card,
  Col,
  Form,
  Input,
  InputNumber,
  Row,
  Select,
  Space,
  Typography,
} from "antd";
import React, { useCallback, useRef, useState, useEffect } from "react";
import {
  DeleteOutlined,
  MinusCircleOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import { DrawingManager, GoogleMap, Polygon } from "@react-google-maps/api";
import { useQuery } from "@tanstack/react-query";
import { getProvince } from "@/lib/api";
import { useTranslations } from "next-intl";

const { Text, Title } = Typography;

const MAP_CONTAINER_STYLE = { width: "100%", height: "350px" };
const DEFAULT_CENTER = { lat: 10.762622, lng: 106.660172 };

interface PlotMapProps {
  index: number;
  initialPath: { lat: number; lng: number }[];
  onUpdate: (path: { lat: number; lng: number }[]) => void;
}

const PlotMap = ({ initialPath, onUpdate }: PlotMapProps) => {
  const t = useTranslations("Factory.external_material");
  const [polygonPath, setPolygonPath] = useState<
    { lat: number; lng: number }[]
  >(initialPath || []);
  const polygonRef = useRef<any>(null);

  useEffect(() => {
    setPolygonPath(initialPath || []);
  }, [initialPath]);

  const onPolygonComplete = useCallback(
    (polygon: any) => {
      const path = polygon.getPath();
      const newCoords = [];
      for (let i = 0; i < path.getLength(); i++) {
        newCoords.push({ lat: path.getAt(i).lat(), lng: path.getAt(i).lng() });
      }
      setPolygonPath(newCoords);
      onUpdate(newCoords);
      polygon.setMap(null);
    },
    [onUpdate],
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
      onUpdate(updatedCoords);
    }
  }, [onUpdate]);

  const clearPolygon = () => {
    setPolygonPath([]);
    onUpdate([]);
  };

  return (
    <Card
      size="small"
      title={t("coordinate_map")}
      extra={
        polygonPath.length > 0 && (
          <Button size="small" danger onClick={clearPolygon}>
            {t("clear_drawing")}
          </Button>
        )
      }>
      <GoogleMap
        mapContainerStyle={MAP_CONTAINER_STYLE}
        center={polygonPath.length > 0 ? polygonPath[0] : DEFAULT_CENTER}
        zoom={12}
        options={{ mapTypeId: "satellite" }}>
        {polygonPath.length === 0 && (
          <DrawingManager
            onPolygonComplete={onPolygonComplete}
            options={{
              drawingControl: true,
              drawingControlOptions: {
                drawingModes: [google.maps.drawing.OverlayType.POLYGON],
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
    </Card>
  );
};

const StepPlots = () => {
  const t = useTranslations("Factory.external_material");
  const tc = useTranslations("Common");
  const form = Form.useFormInstance();

  const { data: provincesData } = useQuery({
    queryKey: ["provinces"],
    queryFn: () => getProvince(),
  });

  return (
    <div style={{ padding: "16px 0" }}>
      <Form.List name="plots">
        {(fields, { add, remove }) => (
          <div style={{ display: "flex", flexDirection: "column", rowGap: 24 }}>
            {fields.map((field, index) => (
              <Card
                key={field.key}
                title={
                  <Title level={5} style={{ margin: 0 }}>
                    {t("lot_number", { index: index + 1 })}
                  </Title>
                }
                extra={
                  <Button
                    type="text"
                    danger
                    icon={<DeleteOutlined />}
                    onClick={() => remove(field.name)}>
                    {t("remove_plot")}
                  </Button>
                }>
                <Row gutter={24}>
                  <Col xs={24} lg={10}>
                    <Form.Item
                      name={[field.name, "plot_name"]}
                      label={t("plot_name")}
                      rules={[
                        { required: true, message: tc("enter_name") },
                      ]}>
                      <Input placeholder={t("plot_name")} />
                    </Form.Item>

                    <Row gutter={16}>
                      <Col span={12}>
                        <Form.Item
                          name={[field.name, "province_id"]}
                          label={t("province")}
                          rules={[
                            { required: true, message: tc("select_province") },
                          ]}>
                          <Select
                            showSearch={{
                              filterOption: (input, option) =>
                                (option?.label ?? "")
                                  .toLowerCase()
                                  .includes(input.toLowerCase()),
                            }}
                            placeholder={tc("select_province")}
                            options={provincesData?.provinces?.map((p) => ({
                              value: p.province_id,
                              label: p.province_name,
                            }))}
                          />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          name={[field.name, "land_area"]}
                          label={t("plot_area_ha")}
                          rules={[
                            { required: true, message: t("yield_required") },
                          ]}>
                          <InputNumber
                            style={{ width: "100%" }}
                            min={0}
                            placeholder="Eg: 2.5"
                          />
                        </Form.Item>
                      </Col>
                    </Row>

                    <Form.Item
                      name={[field.name, "harvest_weight"]}
                      label={t("expected_yield_year")}
                      rules={[{ required: true, message: t("yield_required") }]}>
                      <InputNumber
                        style={{ width: "100%" }}
                        min={0}
                        placeholder="Eg: 5000"
                        formatter={(value) =>
                          `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                        }
                      />
                    </Form.Item>

                    <Form.Item
                      name={[field.name, "address"]}
                      label={t("details_address")}
                      rules={[
                        { required: true, message: tc("address_required") },
                      ]}>
                      <Input.TextArea
                        rows={2}
                        placeholder={tc("enter_address")}
                      />
                    </Form.Item>

                    <Form.Item
                      name={[field.name, "notes"]}
                      label={t("plot_notes")}>
                      <Input.TextArea
                        rows={2}
                        placeholder={tc("no_notes")}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} lg={14}>
                    <Form.Item
                      noStyle
                      dependencies={[["plots", field.name, "coordinates"]]}>
                      {({ getFieldValue }) => {
                        const coords = getFieldValue([
                          "plots",
                          field.name,
                          "coordinates",
                        ]);
                        return (
                          <PlotMap
                            index={index}
                            initialPath={coords}
                            onUpdate={(path) => {
                              form.setFieldValue(
                                ["plots", field.name, "coordinates"],
                                path,
                              );
                            }}
                          />
                        );
                      }}
                    </Form.Item>

                    <Card
                      size="small"
                      title={t("coordinates_detail")}
                      style={{ marginTop: 16 }}
                      styles={{
                        body: { maxHeight: "300px", overflowY: "auto" },
                      }}>
                      <Form.List name={[field.name, "coordinates"]}>
                        {(
                          coordFields,
                          { add: addCoord, remove: removeCoord },
                        ) => (
                          <>
                            {coordFields.map((coordField, cIndex) => (
                              <Space
                                key={coordField.key}
                                align="baseline"
                                style={{ display: "flex", marginBottom: 8 }}>
                                <Form.Item
                                  name={[coordField.name, "lat"]}
                                  label={cIndex === 0 ? t("latitude") : ""}
                                  rules={[
                                    { required: true, message: "Required Lat" },
                                  ]}>
                                  <InputNumber
                                    placeholder="Lat"
                                    style={{ width: "140px" }}
                                  />
                                </Form.Item>
                                <Form.Item
                                  name={[coordField.name, "lng"]}
                                  label={cIndex === 0 ? t("longitude") : ""}
                                  rules={[
                                    { required: true, message: "Required Lng" },
                                  ]}>
                                  <InputNumber
                                    placeholder="Lng"
                                    style={{ width: "140px" }}
                                  />
                                </Form.Item>
                                <MinusCircleOutlined
                                  onClick={() => removeCoord(coordField.name)}
                                  style={{ color: "#ff4d4f" }}
                                />
                              </Space>
                            ))}
                            <Button
                              type="dashed"
                              onClick={() => addCoord()}
                              block
                              icon={<PlusOutlined />}>
                              {t("add_coordinate")}
                            </Button>
                          </>
                        )}
                      </Form.List>
                    </Card>
                  </Col>
                </Row>
              </Card>
            ))}

            <Button
              type="dashed"
              onClick={() => add()}
              block
              icon={<PlusOutlined />}
              style={{ height: 60, fontSize: "16px" }}>
              {t("add_plot")}
            </Button>
            {fields.length === 0 && (
              <Text
                type="danger"
                style={{ textAlign: "center", display: "block" }}>
                {t("plot_required_error")}
              </Text>
            )}
          </div>
        )}
      </Form.List>
    </div>
  );
};

export default StepPlots;
