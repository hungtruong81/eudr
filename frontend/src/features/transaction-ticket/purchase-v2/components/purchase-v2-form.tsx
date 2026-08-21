"use client";
import React, { useState, useEffect } from "react";
import {
  Form,
  Input,
  InputNumber,
  Select,
  Checkbox,
  Button,
  Card,
  Col,
  Row,
  Space,
  Typography,
  Divider,
  Progress,
  Badge,
  Alert,
  Tooltip,
  Flex,
  Tag,
  message,
} from "antd";
import {
  ArrowLeftOutlined,
  SaveOutlined,
  InfoCircleOutlined,
  CarOutlined,
  DatabaseOutlined,
  LockOutlined,
} from "@ant-design/icons";
import { formatVnCurrency } from "@/lib/utils";
import { useQuery } from "@tanstack/react-query";
import { getRawMaterialTank } from "@/features/factory/factory-metadata/raw-material-tank/actions";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { IRawMaterialTank } from "@/features/factory/factory-metadata/raw-material-tank/types";

const { Text, Title } = Typography;

interface IThuMuaFormProps {
  initialValues?: any;
  onSave: (values: any) => void;
  onBack: () => void;
}

interface IBin {
  id: string;
  name: string;
  capacity: number;
  poured: number;
  quality: "loai_1" | "loai_2" | null;
  pH: number;
}

const INITIAL_BINS: IBin[] = Array.from({ length: 8 }, (_, i) => ({
  id: String(i + 1),
  name: `Bình ${i + 1}`,
  capacity: 100,
  poured: 0,
  quality: null,
  pH: 7.0,
}));

const ThuMuaForm = ({ initialValues, onSave, onBack }: IThuMuaFormProps) => {
  const [form] = Form.useForm();
  const [bins, setBins] = useState<IBin[]>(INITIAL_BINS);
  const [activeBinId, setActiveBinId] = useState<string>("1");
  const [hasTransport, setHasTransport] = useState<boolean>(true);

  useEffect(() => {
    if (initialValues) {
      form.setFieldsValue({
        seller_name: initialValues.seller_name,
        buyer_name: initialValues.buyer_name,
        has_transport: initialValues.has_transport,
        tank_id: initialValues.tank_id,
        driver_name: initialValues.transport_info?.driver_name,
        driver_phone: initialValues.transport_info?.driver_phone,
        license_plate: initialValues.transport_info?.license_plate,
        pickup_location: initialValues.transport_info?.pickup_location,
        delivery_location: initialValues.transport_info?.delivery_location,
      });
      setHasTransport(initialValues.has_transport);
      if (initialValues.bins) {
        const loadedBins = INITIAL_BINS.map((b) => {
          const found = initialValues.bins.find(
            (item: any) => item.id === b.id,
          );
          return found ? { ...b, ...found } : b;
        });
        setBins(loadedBins);
      }
    }
  }, [initialValues, form]);

  const activeBin = bins.find((b) => b.id === activeBinId) || bins[0];

  // Active bin editing fields
  const [binCapacity, setBinCapacity] = useState(100);
  const [binPoured, setBinPoured] = useState(0);
  const [binQuality, setBinQuality] = useState<"loai_1" | "loai_2" | null>(
    null,
  );
  const [binPH, setBinPH] = useState(7.0);

  // Sync edit state when active bin changes
  useEffect(() => {
    if (activeBin) {
      setBinCapacity(activeBin.capacity);
      setBinPoured(activeBin.poured);
      setBinQuality(activeBin.quality);
      setBinPH(activeBin.pH);
    }
  }, [activeBin]);

  // Calculate pricing based on pH and Quality
  const getPHMultiplier = (ph: number) => {
    if (ph >= 6.5 && ph <= 7.5) return 1.0;
    if (ph < 6.5) {
      return Math.max(0.7, 1.0 - (7.0 - ph) * 0.1);
    }
    return Math.max(0.7, 1.0 - (ph - 7.0) * 0.1);
  };

  const calculateBinAmount = (
    poured: number,
    quality: string | null,
    ph: number,
  ) => {
    if (!poured || !quality) return 0;
    const tsc = quality === "loai_1" ? 30 : 25; // 30% TSC for Type 1, 25% for Type 2
    const dryWeight = poured * (tsc / 100);
    const basePricePerTSC = 35000; // 35,000 VND per dry kg
    const phMultiplier = getPHMultiplier(ph);
    return dryWeight * basePricePerTSC * phMultiplier;
  };

  const handleUpdateBin = () => {
    if (binPoured > binCapacity) {
      message.error("Lượng mủ đổ vào không thể vượt quá dung tích bình!");
      return;
    }

    if (binPoured > 0 && !binQuality) {
      message.error("Vui lòng chọn loại chất lượng mủ khi có lượng đổ!");
      return;
    }

    const updatedBins = bins.map((b) => {
      if (b.id === activeBinId) {
        return {
          ...b,
          capacity: binCapacity,
          poured: binPoured,
          quality: binPoured === 0 ? null : binQuality, // reset quality if poured is 0
          pH: binPH,
        };
      }
      return b;
    });

    setBins(updatedBins);
    message.success(`Đã cập nhật ${activeBin.name}`);
  };

  // Aggregated values
  const totalWeight = bins.reduce((sum, b) => sum + b.poured, 0);
  const totalAmount = bins.reduce(
    (sum, b) => sum + calculateBinAmount(b.poured, b.quality, b.pH),
    0,
  );

  const handleSubmit = (values: any) => {
    const activeBins = bins.filter((b) => b.poured > 0);
    if (activeBins.length === 0) {
      message.error("Vui lòng khai báo mủ cho ít nhất một bình con!");
      return;
    }

    const ticketData = {
      seller_name: values.seller_name,
      buyer_name: values.buyer_name,
      has_transport: hasTransport,
      total_weight: totalWeight,
      total_amount: totalAmount,
      bins: activeBins,
      ...(hasTransport
        ? {
            transport_info: {
              driver_name: values.driver_name,
              driver_phone: values.driver_phone,
              license_plate: values.license_plate,
              pickup_location: values.pickup_location,
              delivery_location: values.delivery_location,
            },
          }
        : {
            tank_id: values.tank_id,
          }),
    };

    onSave(ticketData);
  };

  const getBinBadgeStatus = (bin: IBin) => {
    if (bin.poured === 0) return "default";
    if (bin.poured >= bin.capacity) return "success";
    return "processing";
  };

  const getBinBadgeText = (bin: IBin) => {
    if (bin.poured === 0) return "Trống";
    if (bin.poured >= bin.capacity) return "Đầy";
    return "Đang chứa";
  };

  return (
    <div style={{ padding: "0 10px" }}>
      <Space orientation="vertical" style={{ width: "100%" }} size="middle">
        {/* Header */}
        <Flex align="center" justify="space-between">
          <Space>
            <Button icon={<ArrowLeftOutlined />} onClick={onBack} />
            <Title level={4} style={{ margin: 0 }}>
              {initialValues
                ? "Chỉnh sửa phiếu thu mua"
                : "Tạo phiếu thu mua mới"}
            </Title>
          </Space>
          <Button
            type="primary"
            icon={<SaveOutlined />}
            onClick={() => form.submit()}
            style={{ height: 40, borderRadius: 6 }}>
            Lưu phiếu
          </Button>
        </Flex>

        <Row gutter={[24, 24]}>
          <Col xs={24} lg={11}>
            <Card
              title="Thông tin Chung & Điều phối"
              style={{ borderRadius: 8 }}>
              <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                initialValues={{
                  seller_name: "Nguyễn Văn A",
                  buyer_name: "HTX Thu Mua Lộc Ninh",
                  has_transport: true,
                }}>
                <Row gutter={16}>
                  <Col span={12}>
                    <Form.Item
                      label="Người bán (Nông hộ)"
                      name="seller_name"
                      rules={[
                        {
                          required: true,
                          message: "Vui lòng nhập tên người bán",
                        },
                      ]}>
                      <Input placeholder="Tên nông hộ" />
                    </Form.Item>
                  </Col>
                  <Col span={12}>
                    <Form.Item
                      label="Người thu mua (HTX)"
                      name="buyer_name"
                      rules={[
                        {
                          required: true,
                          message: "Vui lòng nhập tên người thu mua",
                        },
                      ]}>
                      <Input placeholder="Tên HTX / Người mua" />
                    </Form.Item>
                  </Col>
                </Row>

                <Form.Item name="has_transport" valuePropName="checked">
                  <Checkbox
                    onChange={(e) => setHasTransport(e.target.checked)}
                    style={{ fontSize: "14px", fontWeight: 500 }}>
                    Phiếu thu mua có vận chuyển
                  </Checkbox>
                </Form.Item>

                <Divider style={{ margin: "12px 0" }} />

                {hasTransport ? (
                  <div>
                    <div
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: 8,
                        marginBottom: 12,
                      }}>
                      <CarOutlined style={{ color: "#1890ff", fontSize: 16 }} />
                      <Text strong>Thông tin xe & tài xế vận chuyển</Text>
                    </div>
                    <Row gutter={16}>
                      <Col span={12}>
                        <Form.Item
                          label="Tên tài xế"
                          name="driver_name"
                          rules={[
                            {
                              required: hasTransport,
                              message: "Vui lòng nhập tên tài xế",
                            },
                          ]}>
                          <Input placeholder="Trần Văn Tài" />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          label="Số điện thoại tài xế"
                          name="driver_phone"
                          rules={[
                            {
                              required: hasTransport,
                              message: "Vui lòng nhập SĐT tài xế",
                            },
                          ]}>
                          <Input placeholder="0901234567" />
                        </Form.Item>
                      </Col>
                      <Col span={24}>
                        <Form.Item
                          label="Biển số xe"
                          name="license_plate"
                          rules={[
                            {
                              required: hasTransport,
                              message: "Vui lòng nhập biển số xe",
                            },
                          ]}>
                          <Input placeholder="93A-123.45" />
                        </Form.Item>
                      </Col>
                      <Col span={24}>
                        <Form.Item
                          label="Điểm thu mua (Nơi bốc hàng)"
                          name="pickup_location"
                          initialValue="Vườn cao su Lộc Ninh">
                          <Input placeholder="Địa chỉ điểm bốc mủ" />
                        </Form.Item>
                      </Col>
                      <Col span={24}>
                        <Form.Item
                          label="Điểm hạ hàng (Nhà máy)"
                          name="delivery_location"
                          initialValue="Nhà máy Chế biến Lộc Ninh">
                          <Input placeholder="Tên nhà máy tiếp nhận" />
                        </Form.Item>
                      </Col>
                    </Row>
                  </div>
                ) : (
                  <div>
                    <div
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: 8,
                        marginBottom: 12,
                      }}>
                      <DatabaseOutlined
                        style={{ color: "#fa8c16", fontSize: 16 }}
                      />
                      <Text strong>Đổ trực tiếp vào bồn chứa tại Nhà máy</Text>
                    </div>
                    <Form.Item
                      label="Bồn chứa tiếp nhận mủ"
                      name="tank_id"
                      rules={[
                        {
                          required: !hasTransport,
                          message: "Vui lòng chọn bồn chứa",
                        },
                      ]}>
                      <InfiniteScrollSelect<IRawMaterialTank>
                        queryKey={["tanks"]}
                        placeholder={"Chọn bồn chứa"}
                        fetchFn={(params) =>
                          getRawMaterialTank({
                            ...params,
                          })
                        }
                        mapOption={(item) => ({
                          value: String(item.raw_material_tank_code),
                          label: `${item.raw_material_tank_name} - ${item.current_volume}/${item.capacity}kg`,
                        })}
                      />
                    </Form.Item>
                  </div>
                )}
              </Form>
            </Card>
          </Col>

          <Col xs={24} lg={13}>
            <Card
              title="Quản lý Bình con chứa mủ"
              style={{ borderRadius: 8 }}
              extra={
                <Space>
                  <Text type="secondary">Tổng KL:</Text>
                  <Text strong style={{ fontSize: 16, color: "#1890ff" }}>
                    {totalWeight} kg
                  </Text>
                </Space>
              }>
              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="large">
                <div
                  style={{
                    display: "grid",
                    gridTemplateColumns:
                      "repeat(auto-fill, minmax(130px, 1fr))",
                    gap: "12px",
                  }}>
                  {bins.map((bin) => {
                    const isActive = bin.id === activeBinId;
                    const percent = Math.min(
                      100,
                      Math.round((bin.poured / bin.capacity) * 100),
                    );

                    return (
                      <button
                        key={bin.id}
                        type="button"
                        onClick={() => setActiveBinId(bin.id)}
                        style={{
                          display: "flex",
                          flexDirection: "column",
                          alignItems: "stretch",
                          padding: "10px",
                          borderRadius: "8px",
                          border: isActive
                            ? "2px solid #1890ff"
                            : "1px solid #d9d9d9",
                          backgroundColor: isActive ? "#e6f7ff" : "#fff",
                          cursor: "pointer",
                          textAlign: "left",
                          transition: "all 0.2s",
                          boxShadow: isActive
                            ? "0 2px 8px rgba(24, 144, 255, 0.15)"
                            : "none",
                        }}>
                        <Flex
                          justify="space-between"
                          align="center"
                          style={{ width: "100%", marginBottom: 6 }}>
                          <span
                            style={{
                              fontWeight: 600,
                              fontSize: "14px",
                              color: isActive ? "#1890ff" : "#262626",
                            }}>
                            {bin.name}
                          </span>
                          <Badge status={getBinBadgeStatus(bin)} />
                        </Flex>

                        <Progress
                          percent={percent}
                          size="small"
                          showInfo={false}
                          strokeColor={bin.poured > 0 ? "#52c41a" : "#d9d9d9"}
                          style={{ marginBottom: 6 }}
                        />

                        <div style={{ fontSize: "12px", color: "#8c8c8c" }}>
                          Đã đổ:{" "}
                          <span style={{ fontWeight: 500, color: "#595959" }}>
                            {bin.poured}kg
                          </span>
                        </div>
                        <div style={{ fontSize: "12px", color: "#8c8c8c" }}>
                          Còn lại:{" "}
                          <span style={{ fontWeight: 500, color: "#595959" }}>
                            {bin.capacity - bin.poured}kg
                          </span>
                        </div>

                        {bin.poured > 0 && (
                          <div
                            style={{
                              marginTop: 4,
                              display: "flex",
                              gap: 4,
                              flexWrap: "wrap",
                            }}>
                            <Tag
                              color="blue"
                              style={{
                                fontSize: "10px",
                                margin: 0,
                                padding: "0 4px",
                              }}>
                              {bin.quality === "loai_1" ? "L1" : "L2"}
                            </Tag>
                            <Tag
                              color="cyan"
                              style={{
                                fontSize: "10px",
                                margin: 0,
                                padding: "0 4px",
                              }}>
                              pH {bin.pH}
                            </Tag>
                          </div>
                        )}
                      </button>
                    );
                  })}
                </div>

                <Card
                  title={`Cập nhật lượng đổ & chất lượng: ${activeBin.name}`}
                  size="small"
                  styles={{ header: { backgroundColor: "#fafafa" } }}
                  style={{ borderRadius: 6, border: "1px solid #f0f0f0" }}>
                  <Row gutter={[16, 16]}>
                    <Col xs={24} sm={12}>
                      <div style={{ marginBottom: 4 }}>
                        <Text type="secondary">Dung tích bình chứa (kg)</Text>
                      </div>
                      <InputNumber
                        min={10}
                        max={1000}
                        value={binCapacity}
                        onChange={(val) => setBinCapacity(val || 100)}
                        style={{ width: "100%" }}
                      />
                    </Col>

                    <Col xs={24} sm={12}>
                      <div style={{ marginBottom: 4 }}>
                        <Text type="secondary">Lượng mủ đổ vào (kg)</Text>
                      </div>
                      <InputNumber
                        min={0}
                        max={binCapacity}
                        value={binPoured}
                        onChange={(val) => setBinPoured(val || 0)}
                        style={{ width: "100%" }}
                      />
                    </Col>

                    <Col xs={24} sm={12}>
                      <div style={{ marginBottom: 4 }}>
                        <Text type="secondary">Loại chất lượng mủ</Text>
                        {activeBin.poured > 0 && (
                          <Tooltip title="Chất lượng đã được khóa theo lần đổ đầu tiên của bình chứa. Cần đưa lượng mủ về 0 để thiết lập lại chất lượng.">
                            <LockOutlined
                              style={{ marginLeft: 6, color: "#fa8c16" }}
                            />
                          </Tooltip>
                        )}
                      </div>
                      <Select
                        value={binQuality}
                        onChange={(val) => setBinQuality(val)}
                        style={{ width: "100%" }}
                        disabled={activeBin.poured > 0}
                        placeholder="Chọn chất lượng mủ"
                        options={[
                          { label: "Chưa chọn chất lượng", value: null },
                          {
                            label: "L1",
                            value: "loai_1",
                          },
                          {
                            label: "L2",
                            value: "loai_2",
                          },
                          {
                            label: "L3",
                            value: "loai_3",
                          },
                          {
                            label: "Mix",
                            value: "mix",
                          },
                          {
                            label: "NA",
                            value: "na",
                          },
                        ]}
                      />
                    </Col>

                    <Col xs={24} sm={12}>
                      <div style={{ marginBottom: 4 }}>
                        <Text type="secondary">Chỉ số độ pH</Text>
                      </div>
                      <InputNumber
                        min={0}
                        max={14}
                        step={0.1}
                        value={binPH}
                        onChange={(val) => setBinPH(val || 7.0)}
                        style={{ width: "100%" }}
                      />
                    </Col>

                    <Col span={24}>
                      <Flex
                        justify="space-between"
                        align="center"
                        style={{
                          backgroundColor: "#f9f9f9",
                          padding: "12px",
                          borderRadius: "6px",
                        }}>
                        <div>
                          <Text type="secondary" style={{ fontSize: "12px" }}>
                            Thành tiền của bình con này:
                          </Text>
                          <div
                            style={{
                              fontSize: "16px",
                              fontWeight: "bold",
                              color: "#52c41a",
                            }}>
                            {formatVnCurrency(
                              calculateBinAmount(binPoured, binQuality, binPH),
                            )}
                          </div>
                        </div>

                        <Button type="primary" onClick={handleUpdateBin}>
                          Xác nhận lưu vào {activeBin.name}
                        </Button>
                      </Flex>
                    </Col>
                  </Row>
                </Card>

                {/* Summary Box */}
                <Card
                  styles={{
                    body: { padding: "16px", backgroundColor: "#fafafa" },
                  }}
                  style={{ borderRadius: 6 }}>
                  <Flex
                    justify="space-between"
                    align="center"
                    wrap
                    gap="middle">
                    <div>
                      <div style={{ fontSize: "14px", color: "#595959" }}>
                        Tổng khối lượng thu mua:
                      </div>
                      <div
                        style={{
                          fontSize: "24px",
                          fontWeight: "bold",
                          color: "#262626",
                        }}>
                        {totalWeight} kg
                      </div>
                    </div>
                    <div style={{ textAlign: "right" }}>
                      <div style={{ fontSize: "14px", color: "#595959" }}>
                        Tổng giá trị thanh toán dự kiến:
                      </div>
                      <div
                        style={{
                          fontSize: "24px",
                          fontWeight: "bold",
                          color: "#1890ff",
                        }}>
                        {formatVnCurrency(totalAmount)}
                      </div>
                    </div>
                  </Flex>
                </Card>
              </Space>
            </Card>
          </Col>
        </Row>
      </Space>
    </div>
  );
};

export default ThuMuaForm;
