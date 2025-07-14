import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { 
  CheckCircle, 
  Clock, 
  AlertCircle, 
  ArrowRight,
  Package,
  MapPin,
  User,
  Scan
} from 'lucide-react';
import { BarcodeScanner } from '@/components/BarcodeScanner';

interface WorkflowTask {
  id: string;
  type: 'pick' | 'pack' | 'putaway' | 'count';
  title: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
  status: 'pending' | 'in_progress' | 'completed';
  location: string;
  items: WorkflowItem[];
  estimatedTime: number;
  assignedTo?: string;
}

interface WorkflowItem {
  id: string;
  productCode: string;
  productName: string;
  quantity: number;
  location: string;
  completed: boolean;
}

interface MobileWorkflowProps {
  className?: string;
}

export const MobileWorkflow: React.FC<MobileWorkflowProps> = ({ className }) => {
  const [tasks, setTasks] = useState<WorkflowTask[]>([]);
  const [activeTask, setActiveTask] = useState<WorkflowTask | null>(null);
  const [showScanner, setShowScanner] = useState(false);
  const [scanningFor, setScanningFor] = useState<'location' | 'product' | null>(null);
  const [currentItemIndex, setCurrentItemIndex] = useState(0);

  useEffect(() => {
    loadTasks();
  }, []);

  const loadTasks = async () => {
    // Mock tasks data
    const mockTasks: WorkflowTask[] = [
      {
        id: '1',
        type: 'pick',
        title: 'Pick Order #12345',
        description: 'Pick 5 items for customer order',
        priority: 'high',
        status: 'pending',
        location: 'A-01-01',
        estimatedTime: 15,
        items: [
          {
            id: '1',
            productCode: 'ABC123',
            productName: 'Widget A',
            quantity: 2,
            location: 'A-01-01',
            completed: false
          },
          {
            id: '2',
            productCode: 'DEF456',
            productName: 'Widget B',
            quantity: 3,
            location: 'A-01-02',
            completed: false
          }
        ]
      },
      {
        id: '2',
        type: 'putaway',
        title: 'Putaway Received Items',
        description: 'Store 10 items in designated locations',
        priority: 'medium',
        status: 'pending',
        location: 'B-02-01',
        estimatedTime: 20,
        items: [
          {
            id: '3',
            productCode: 'GHI789',
            productName: 'Widget C',
            quantity: 5,
            location: 'B-02-01',
            completed: false
          }
        ]
      }
    ];
    setTasks(mockTasks);
  };

  const startTask = (task: WorkflowTask) => {
    const updatedTask = { ...task, status: 'in_progress' as const };
    setActiveTask(updatedTask);
    setCurrentItemIndex(0);
    
    // Update tasks list
    setTasks(tasks.map(t => t.id === task.id ? updatedTask : t));
  };

  const completeItem = (itemId: string) => {
    if (!activeTask) return;

    const updatedItems = activeTask.items.map(item =>
      item.id === itemId ? { ...item, completed: true } : item
    );

    const updatedTask = { ...activeTask, items: updatedItems };
    setActiveTask(updatedTask);

    // Check if all items are completed
    const allCompleted = updatedItems.every(item => item.completed);
    if (allCompleted) {
      completeTask();
    } else {
      // Move to next item
      const nextIndex = updatedItems.findIndex(item => !item.completed);
      setCurrentItemIndex(nextIndex >= 0 ? nextIndex : 0);
    }
  };

  const completeTask = () => {
    if (!activeTask) return;

    const completedTask = { ...activeTask, status: 'completed' as const };
    setTasks(tasks.map(t => t.id === activeTask.id ? completedTask : t));
    setActiveTask(null);
    setCurrentItemIndex(0);
  };

  const handleScan = (result: string) => {
    if (scanningFor === 'location') {
      // Verify location scan
      console.log('Location scanned:', result);
    } else if (scanningFor === 'product') {
      // Verify product scan
      console.log('Product scanned:', result);
      if (activeTask) {
        const currentItem = activeTask.items[currentItemIndex];
        if (currentItem && result === currentItem.productCode) {
          completeItem(currentItem.id);
        }
      }
    }
    setShowScanner(false);
    setScanningFor(null);
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'high':
        return 'bg-red-100 text-red-800';
      case 'medium':
        return 'bg-yellow-100 text-yellow-800';
      case 'low':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <CheckCircle className="h-4 w-4 text-green-600" />;
      case 'in_progress':
        return <Clock className="h-4 w-4 text-blue-600" />;
      default:
        return <AlertCircle className="h-4 w-4 text-gray-600" />;
    }
  };

  const getTaskTypeIcon = (type: string) => {
    switch (type) {
      case 'pick':
        return <Package className="h-5 w-5" />;
      case 'putaway':
        return <MapPin className="h-5 w-5" />;
      default:
        return <Package className="h-5 w-5" />;
    }
  };

  if (activeTask) {
    const currentItem = activeTask.items[currentItemIndex];
    const completedItems = activeTask.items.filter(item => item.completed).length;
    const totalItems = activeTask.items.length;

    return (
      <div className={`min-h-screen bg-gray-50 ${className}`}>
        {/* Task Header */}
        <div className="bg-white shadow-sm border-b p-4">
          <div className="flex items-center justify-between mb-2">
            <h1 className="text-lg font-semibold">{activeTask.title}</h1>
            <Badge className={getPriorityColor(activeTask.priority)}>
              {activeTask.priority}
            </Badge>
          </div>
          <div className="flex items-center text-sm text-gray-600">
            <span>{completedItems}/{totalItems} items completed</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2 mt-2">
            <div 
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${(completedItems / totalItems) * 100}%` }}
            />
          </div>
        </div>

        {/* Current Item */}
        {currentItem && (
          <div className="p-4">
            <Card className="border-blue-200 bg-blue-50">
              <CardHeader className="pb-3">
                <CardTitle className="text-lg flex items-center">
                  <Package className="h-5 w-5 mr-2 text-blue-600" />
                  Current Item
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <p className="font-semibold text-lg">{currentItem.productName}</p>
                  <p className="text-gray-600">{currentItem.productCode}</p>
                </div>
                
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">Quantity:</span>
                  <span className="font-semibold">{currentItem.quantity}</span>
                </div>
                
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">Location:</span>
                  <span className="font-semibold">{currentItem.location}</span>
                </div>

                <div className="flex space-x-2 pt-2">
                  <Button
                    onClick={() => {
                      setScanningFor('location');
                      setShowScanner(true);
                    }}
                    variant="outline"
                    className="flex-1"
                  >
                    <Scan className="h-4 w-4 mr-2" />
                    Scan Location
                  </Button>
                  <Button
                    onClick={() => {
                      setScanningFor('product');
                      setShowScanner(true);
                    }}
                    className="flex-1 bg-blue-600 hover:bg-blue-700"
                  >
                    <Scan className="h-4 w-4 mr-2" />
                    Scan Product
                  </Button>
                </div>

                <Button
                  onClick={() => completeItem(currentItem.id)}
                  className="w-full bg-green-600 hover:bg-green-700"
                >
                  <CheckCircle className="h-4 w-4 mr-2" />
                  Mark Complete
                </Button>
              </CardContent>
            </Card>
          </div>
        )}

        {/* All Items List */}
        <div className="p-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-lg">All Items</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {activeTask.items.map((item, index) => (
                <div
                  key={item.id}
                  className={`p-3 border rounded-lg ${
                    item.completed 
                      ? 'bg-green-50 border-green-200' 
                      : index === currentItemIndex
                      ? 'bg-blue-50 border-blue-200'
                      : 'bg-white border-gray-200'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex-1">
                      <p className="font-medium">{item.productName}</p>
                      <p className="text-sm text-gray-600">{item.productCode}</p>
                      <p className="text-sm text-gray-600">Qty: {item.quantity} | Loc: {item.location}</p>
                    </div>
                    <div className="flex items-center">
                      {item.completed ? (
                        <CheckCircle className="h-5 w-5 text-green-600" />
                      ) : index === currentItemIndex ? (
                        <Clock className="h-5 w-5 text-blue-600" />
                      ) : (
                        <AlertCircle className="h-5 w-5 text-gray-400" />
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>

        {/* Scanner Modal */}
        {showScanner && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div className="bg-white rounded-lg w-full max-w-md">
              <div className="p-4 border-b">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-semibold">
                    Scan {scanningFor === 'location' ? 'Location' : 'Product'}
                  </h3>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setShowScanner(false);
                      setScanningFor(null);
                    }}
                  >
                    ×
                  </Button>
                </div>
              </div>
              <div className="p-4">
                <BarcodeScanner
                  onScan={handleScan}
                  onError={(error) => console.error('Scanner error:', error)}
                />
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className={`min-h-screen bg-gray-50 ${className}`}>
      {/* Header */}
      <div className="bg-white shadow-sm border-b p-4">
        <h1 className="text-lg font-semibold">My Tasks</h1>
        <p className="text-sm text-gray-600">{tasks.length} tasks assigned</p>
      </div>

      {/* Tasks List */}
      <div className="p-4 space-y-4">
        {tasks.map((task) => (
          <Card key={task.id} className="cursor-pointer hover:shadow-md transition-shadow">
            <CardContent className="p-4">
              <div className="flex items-start justify-between mb-3">
                <div className="flex items-center">
                  <div className="text-blue-600 mr-3">
                    {getTaskTypeIcon(task.type)}
                  </div>
                  <div>
                    <h3 className="font-semibold">{task.title}</h3>
                    <p className="text-sm text-gray-600">{task.description}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  {getStatusIcon(task.status)}
                  <Badge className={getPriorityColor(task.priority)}>
                    {task.priority}
                  </Badge>
                </div>
              </div>

              <div className="flex items-center justify-between text-sm text-gray-600 mb-3">
                <span>📍 {task.location}</span>
                <span>⏱️ {task.estimatedTime} min</span>
                <span>📦 {task.items.length} items</span>
              </div>

              {task.status === 'pending' && (
                <Button
                  onClick={() => startTask(task)}
                  className="w-full bg-blue-600 hover:bg-blue-700"
                >
                  Start Task
                  <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              )}

              {task.status === 'completed' && (
                <div className="flex items-center justify-center text-green-600 font-medium">
                  <CheckCircle className="h-4 w-4 mr-2" />
                  Completed
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
};