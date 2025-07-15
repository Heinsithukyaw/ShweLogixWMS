import React, { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Play, 
  Save, 
  Upload, 
  Download,
  Copy,
  RotateCcw,
  ZoomIn,
  ZoomOut,
  Grid,
  Move,
  Square,
  Circle,
  Package,
  Truck,
  Settings
} from 'lucide-react';

interface LayoutElement {
  id: string;
  type: string;
  name: string;
  x: number;
  y: number;
  width: number;
  height: number;
  rotation: number;
  properties: any;
  color: string;
}

interface SimulationResult {
  kpiPredictions: {
    orderFulfillmentTime: number;
    laborProductivity: number;
    spaceEfficiency: number;
    throughputCapacity: number;
    costPerOrder: number;
    accuracyRate: number;
  };
  performanceMetrics: {
    travelTimeReduction: number;
    spaceOptimization: number;
    throughputImprovement: number;
    laborEfficiencyGain: number;
    costSavings: number;
  };
}

interface LayoutSimulationToolProps {
  className?: string;
}

export const LayoutSimulationTool: React.FC<LayoutSimulationToolProps> = ({ className }) => {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [elements, setElements] = useState<LayoutElement[]>([]);
  const [selectedElement, setSelectedElement] = useState<LayoutElement | null>(null);
  const [selectedTool, setSelectedTool] = useState<string>('select');
  const [zoom, setZoom] = useState(1);
  const [panOffset, setPanOffset] = useState({ x: 0, y: 0 });
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [simulationResults, setSimulationResults] = useState<SimulationResult | null>(null);
  const [isSimulating, setIsSimulating] = useState(false);
  const [showGrid, setShowGrid] = useState(true);

  const elementTypes = [
    { type: 'storage_rack', name: 'Storage Rack', icon: Package, color: '#3B82F6' },
    { type: 'picking_station', name: 'Picking Station', icon: Square, color: '#10B981' },
    { type: 'packing_station', name: 'Packing Station', icon: Square, color: '#F59E0B' },
    { type: 'receiving_dock', name: 'Receiving Dock', icon: Truck, color: '#8B5CF6' },
    { type: 'shipping_dock', name: 'Shipping Dock', icon: Truck, color: '#EF4444' },
    { type: 'aisle', name: 'Aisle', icon: Move, color: '#6B7280' },
    { type: 'office', name: 'Office', icon: Square, color: '#F97316' }
  ];

  useEffect(() => {
    drawCanvas();
  }, [elements, selectedElement, zoom, panOffset, showGrid]);

  const drawCanvas = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Save context
    ctx.save();

    // Apply zoom and pan
    ctx.scale(zoom, zoom);
    ctx.translate(panOffset.x, panOffset.y);

    // Draw grid
    if (showGrid) {
      drawGrid(ctx, canvas.width, canvas.height);
    }

    // Draw elements
    elements.forEach(element => {
      drawElement(ctx, element);
    });

    // Draw selection highlight
    if (selectedElement) {
      drawSelectionHighlight(ctx, selectedElement);
    }

    // Restore context
    ctx.restore();
  };

  const drawGrid = (ctx: CanvasRenderingContext2D, width: number, height: number) => {
    const gridSize = 20;
    ctx.strokeStyle = '#E5E7EB';
    ctx.lineWidth = 1;

    for (let x = 0; x <= width / zoom; x += gridSize) {
      ctx.beginPath();
      ctx.moveTo(x, 0);
      ctx.lineTo(x, height / zoom);
      ctx.stroke();
    }

    for (let y = 0; y <= height / zoom; y += gridSize) {
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(width / zoom, y);
      ctx.stroke();
    }
  };

  const drawElement = (ctx: CanvasRenderingContext2D, element: LayoutElement) => {
    ctx.save();
    
    // Move to element position
    ctx.translate(element.x + element.width / 2, element.y + element.height / 2);
    ctx.rotate((element.rotation * Math.PI) / 180);
    
    // Draw element
    ctx.fillStyle = element.color;
    ctx.strokeStyle = '#374151';
    ctx.lineWidth = 2;
    
    ctx.fillRect(-element.width / 2, -element.height / 2, element.width, element.height);
    ctx.strokeRect(-element.width / 2, -element.height / 2, element.width, element.height);
    
    // Draw label
    ctx.fillStyle = '#FFFFFF';
    ctx.font = '12px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(element.name, 0, 4);
    
    ctx.restore();
  };

  const drawSelectionHighlight = (ctx: CanvasRenderingContext2D, element: LayoutElement) => {
    ctx.save();
    ctx.strokeStyle = '#3B82F6';
    ctx.lineWidth = 3;
    ctx.setLineDash([5, 5]);
    ctx.strokeRect(element.x - 2, element.y - 2, element.width + 4, element.height + 4);
    
    // Draw resize handles
    const handles = [
      { x: element.x - 4, y: element.y - 4 },
      { x: element.x + element.width, y: element.y - 4 },
      { x: element.x + element.width, y: element.y + element.height },
      { x: element.x - 4, y: element.y + element.height }
    ];
    
    ctx.fillStyle = '#3B82F6';
    handles.forEach(handle => {
      ctx.fillRect(handle.x, handle.y, 8, 8);
    });
    
    ctx.restore();
  };

  const handleCanvasClick = (event: React.MouseEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const x = (event.clientX - rect.left - panOffset.x * zoom) / zoom;
    const y = (event.clientY - rect.top - panOffset.y * zoom) / zoom;

    if (selectedTool === 'select') {
      // Find clicked element
      const clickedElement = elements.find(element =>
        x >= element.x && x <= element.x + element.width &&
        y >= element.y && y <= element.y + element.height
      );
      setSelectedElement(clickedElement || null);
    } else {
      // Add new element
      const elementType = elementTypes.find(type => type.type === selectedTool);
      if (elementType) {
        const newElement: LayoutElement = {
          id: `element_${Date.now()}`,
          type: selectedTool,
          name: elementType.name,
          x: x - 50,
          y: y - 25,
          width: 100,
          height: 50,
          rotation: 0,
          properties: {},
          color: elementType.color
        };
        setElements([...elements, newElement]);
        setSelectedElement(newElement);
        setSelectedTool('select');
      }
    }
  };

  const handleElementMove = (elementId: string, deltaX: number, deltaY: number) => {
    setElements(elements.map(element =>
      element.id === elementId
        ? { ...element, x: element.x + deltaX, y: element.y + deltaY }
        : element
    ));
  };

  const handleElementResize = (elementId: string, newWidth: number, newHeight: number) => {
    setElements(elements.map(element =>
      element.id === elementId
        ? { ...element, width: Math.max(20, newWidth), height: Math.max(20, newHeight) }
        : element
    ));
  };

  const runSimulation = async () => {
    setIsSimulating(true);
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    const mockResults: SimulationResult = {
      kpiPredictions: {
        orderFulfillmentTime: 45,
        laborProductivity: 92.5,
        spaceEfficiency: 78.3,
        throughputCapacity: 1250,
        costPerOrder: 12.50,
        accuracyRate: 99.2
      },
      performanceMetrics: {
        travelTimeReduction: 15.5,
        spaceOptimization: 22.3,
        throughputImprovement: 18.7,
        laborEfficiencyGain: 12.4,
        costSavings: 8500
      }
    };
    
    setSimulationResults(mockResults);
    setIsSimulating(false);
  };

  const saveLayout = () => {
    const layoutData = {
      elements,
      metadata: {
        name: 'Warehouse Layout',
        created: new Date().toISOString(),
        version: '1.0'
      }
    };
    
    const blob = new Blob([JSON.stringify(layoutData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'warehouse-layout.json';
    a.click();
    URL.revokeObjectURL(url);
  };

  const loadLayout = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const layoutData = JSON.parse(e.target?.result as string);
        setElements(layoutData.elements || []);
        setSelectedElement(null);
      } catch (error) {
        console.error('Error loading layout:', error);
      }
    };
    reader.readAsText(file);
  };

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Layout Simulation Tool</h1>
          <p className="text-gray-600">Design and simulate warehouse layouts with drag-and-drop editor</p>
        </div>
        <div className="flex items-center space-x-2">
          <Button onClick={runSimulation} disabled={isSimulating}>
            <Play className="h-4 w-4 mr-2" />
            {isSimulating ? 'Simulating...' : 'Run Simulation'}
          </Button>
          <Button variant="outline" onClick={saveLayout}>
            <Save className="h-4 w-4 mr-2" />
            Save
          </Button>
          <label className="cursor-pointer">
            <Button variant="outline" asChild>
              <span>
                <Upload className="h-4 w-4 mr-2" />
                Load
              </span>
            </Button>
            <input
              type="file"
              accept=".json"
              onChange={loadLayout}
              className="hidden"
            />
          </label>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Toolbar */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Tools</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Selection Tool */}
            <Button
              variant={selectedTool === 'select' ? 'default' : 'outline'}
              className="w-full justify-start"
              onClick={() => setSelectedTool('select')}
            >
              <Move className="h-4 w-4 mr-2" />
              Select
            </Button>

            {/* Element Types */}
            <div className="space-y-2">
              <h4 className="font-semibold text-sm">Elements</h4>
              {elementTypes.map((elementType) => (
                <Button
                  key={elementType.type}
                  variant={selectedTool === elementType.type ? 'default' : 'outline'}
                  className="w-full justify-start"
                  onClick={() => setSelectedTool(elementType.type)}
                >
                  <elementType.icon className="h-4 w-4 mr-2" />
                  {elementType.name}
                </Button>
              ))}
            </div>

            {/* View Controls */}
            <div className="space-y-2">
              <h4 className="font-semibold text-sm">View</h4>
              <div className="flex space-x-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setZoom(Math.min(2, zoom + 0.1))}
                >
                  <ZoomIn className="h-4 w-4" />
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setZoom(Math.max(0.5, zoom - 0.1))}
                >
                  <ZoomOut className="h-4 w-4" />
                </Button>
                <Button
                  variant={showGrid ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setShowGrid(!showGrid)}
                >
                  <Grid className="h-4 w-4" />
                </Button>
              </div>
            </div>

            {/* Element Properties */}
            {selectedElement && (
              <div className="space-y-2">
                <h4 className="font-semibold text-sm">Properties</h4>
                <div className="space-y-2">
                  <div>
                    <label className="text-xs text-gray-600">Name</label>
                    <input
                      type="text"
                      value={selectedElement.name}
                      onChange={(e) => {
                        const updatedElements = elements.map(el =>
                          el.id === selectedElement.id ? { ...el, name: e.target.value } : el
                        );
                        setElements(updatedElements);
                        setSelectedElement({ ...selectedElement, name: e.target.value });
                      }}
                      className="w-full px-2 py-1 text-sm border rounded"
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <label className="text-xs text-gray-600">Width</label>
                      <input
                        type="number"
                        value={selectedElement.width}
                        onChange={(e) => handleElementResize(selectedElement.id, parseInt(e.target.value), selectedElement.height)}
                        className="w-full px-2 py-1 text-sm border rounded"
                      />
                    </div>
                    <div>
                      <label className="text-xs text-gray-600">Height</label>
                      <input
                        type="number"
                        value={selectedElement.height}
                        onChange={(e) => handleElementResize(selectedElement.id, selectedElement.width, parseInt(e.target.value))}
                        className="w-full px-2 py-1 text-sm border rounded"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Canvas */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Layout Editor</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="border border-gray-300 rounded-lg overflow-hidden">
              <canvas
                ref={canvasRef}
                width={800}
                height={600}
                onClick={handleCanvasClick}
                className="cursor-crosshair"
                style={{ display: 'block' }}
              />
            </div>
          </CardContent>
        </Card>

        {/* Simulation Results */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Simulation Results</CardTitle>
          </CardHeader>
          <CardContent>
            {simulationResults ? (
              <div className="space-y-4">
                <div>
                  <h4 className="font-semibold text-sm mb-2">KPI Predictions</h4>
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Order Fulfillment</span>
                      <span>{simulationResults.kpiPredictions.orderFulfillmentTime} min</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Labor Productivity</span>
                      <span>{simulationResults.kpiPredictions.laborProductivity}%</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Space Efficiency</span>
                      <span>{simulationResults.kpiPredictions.spaceEfficiency}%</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Throughput</span>
                      <span>{simulationResults.kpiPredictions.throughputCapacity}/day</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Cost per Order</span>
                      <span>${simulationResults.kpiPredictions.costPerOrder}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Accuracy Rate</span>
                      <span>{simulationResults.kpiPredictions.accuracyRate}%</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h4 className="font-semibold text-sm mb-2">Performance Improvements</h4>
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Travel Time</span>
                      <Badge variant="secondary" className="text-green-600">
                        -{simulationResults.performanceMetrics.travelTimeReduction}%
                      </Badge>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Space Optimization</span>
                      <Badge variant="secondary" className="text-green-600">
                        +{simulationResults.performanceMetrics.spaceOptimization}%
                      </Badge>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Throughput</span>
                      <Badge variant="secondary" className="text-green-600">
                        +{simulationResults.performanceMetrics.throughputImprovement}%
                      </Badge>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Labor Efficiency</span>
                      <Badge variant="secondary" className="text-green-600">
                        +{simulationResults.performanceMetrics.laborEfficiencyGain}%
                      </Badge>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Cost Savings</span>
                      <Badge variant="secondary" className="text-green-600">
                        ${simulationResults.performanceMetrics.costSavings}/month
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <div className="text-center text-gray-500 py-8">
                <Settings className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>Run simulation to see results</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};