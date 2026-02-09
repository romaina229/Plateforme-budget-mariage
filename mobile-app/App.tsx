import AsyncStorage from '@react-native-async-storage/async-storage';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';

type User = { id: number; username: string; fullname?: string };
type Expense = {
  id: number;
  name: string;
  category_id: number;
  category_name?: string;
  quantity: number;
  unit_price: number;
  frequency: number;
  paid: number;
  payment_date?: string;
};
type Stats = {
  grand_total: number;
  paid_total: number;
  unpaid_total: number;
  payment_percentage: number;
  total_items: number;
  paid_items: number;
  unpaid_items: number;
};
type PlannerTask = { id: string; title: string; done: boolean };

type Screen = 'dashboard' | 'expenses' | 'planner' | 'settings';

const DEFAULT_API_URL = 'http://10.0.2.2/Plateforme-budget-mariage';

export default function App() {
  const [apiBase, setApiBase] = useState(DEFAULT_API_URL);
  const [isLoading, setIsLoading] = useState(true);
  const [loggedIn, setLoggedIn] = useState(false);
  const [user, setUser] = useState<User | null>(null);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [stats, setStats] = useState<Stats | null>(null);
  const [expenses, setExpenses] = useState<Expense[]>([]);
  const [newExpense, setNewExpense] = useState({ name: '', quantity: '1', unit_price: '', frequency: '1', category_id: '1' });
  const [plannerTasks, setPlannerTasks] = useState<PlannerTask[]>([]);
  const [activeScreen, setActiveScreen] = useState<Screen>('dashboard');

  useEffect(() => {
    bootstrap();
  }, []);

  const totalByList = useMemo(
    () => expenses.reduce((sum, item) => sum + Number(item.quantity) * Number(item.unit_price) * Number(item.frequency), 0),
    [expenses],
  );

  async function bootstrap() {
    try {
      const savedApi = await AsyncStorage.getItem('api_base_url');
      if (savedApi) setApiBase(savedApi);

      const savedTasks = await AsyncStorage.getItem('planner_tasks');
      if (savedTasks) {
        setPlannerTasks(JSON.parse(savedTasks));
      } else {
        const defaults: PlannerTask[] = [
          { id: '1', title: 'Réserver la salle', done: false },
          { id: '2', title: 'Valider le traiteur', done: false },
          { id: '3', title: 'Signer le contrat photographe', done: false },
        ];
        setPlannerTasks(defaults);
      }

      await checkSession(savedApi || apiBase);
    } finally {
      setIsLoading(false);
    }
  }

  async function request(path: string, options: RequestInit = {}, customApi?: string) {
    const base = customApi || apiBase;
    const response = await fetch(`${base}${path}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
      credentials: 'include',
    });

    const data = await response.json();
    if (!response.ok) throw new Error(data?.message || 'Erreur réseau');
    return data;
  }

  async function checkSession(customApi?: string) {
    try {
      const data = await request('/api/auth_api.php?action=check', {}, customApi);
      setLoggedIn(Boolean(data.logged_in));
      setUser(data.user || null);
      if (data.logged_in) await loadDashboard(customApi);
    } catch {
      setLoggedIn(false);
      setUser(null);
    }
  }

  async function login() {
    if (!username || !password) {
      Alert.alert('Champs requis', 'Veuillez renseigner utilisateur et mot de passe.');
      return;
    }

    try {
      const data = await request('/api/auth_api.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ username, password }),
      });

      if (!data.success) {
        Alert.alert('Connexion refusée', data.message || 'Identifiants incorrects');
        return;
      }

      setLoggedIn(true);
      setUser(data.user || null);
      await loadDashboard();
      setActiveScreen('dashboard');
    } catch (error) {
      Alert.alert('Erreur', (error as Error).message);
    }
  }

  async function loadDashboard(customApi?: string) {
    const [statsRes, expensesRes] = await Promise.all([
      request('/api/api.php?action=get_stats', {}, customApi),
      request('/api/api.php?action=get_all', {}, customApi),
    ]);
    setStats(statsRes.data || null);
    setExpenses(expensesRes.data || []);
  }

  async function addExpense() {
    if (!newExpense.name || !newExpense.unit_price) {
      Alert.alert('Champs requis', 'Nom et prix unitaire sont obligatoires.');
      return;
    }

    await request('/api/api.php?action=add', {
      method: 'POST',
      body: JSON.stringify({
        ...newExpense,
        quantity: Number(newExpense.quantity),
        unit_price: Number(newExpense.unit_price),
        frequency: Number(newExpense.frequency),
        category_id: Number(newExpense.category_id),
      }),
    });

    setNewExpense({ name: '', quantity: '1', unit_price: '', frequency: '1', category_id: '1' });
    await loadDashboard();
  }

  async function togglePaid(id: number) {
    await request(`/api/api.php?action=toggle_paid&id=${id}`);
    await loadDashboard();
  }

  async function removeExpense(id: number) {
    await request(`/api/api.php?action=delete&id=${id}`);
    await loadDashboard();
  }

  async function persistTasks(next: PlannerTask[]) {
    setPlannerTasks(next);
    await AsyncStorage.setItem('planner_tasks', JSON.stringify(next));
  }

  async function saveSettings() {
    await AsyncStorage.setItem('api_base_url', apiBase.trim());
    await checkSession(apiBase.trim());
    Alert.alert('Succès', 'Paramètres sauvegardés.');
  }

  if (isLoading) {
    return (
      <SafeAreaView style={styles.centered}>
        <ActivityIndicator size="large" color="#8b4f8d" />
      </SafeAreaView>
    );
  }

  if (!loggedIn) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="dark" />
        <View style={styles.authCard}>
          <Text style={styles.title}>PJPM Mobile</Text>
          <Text style={styles.subtitle}>Gestion mariage et évènements festifs</Text>
          <TextInput placeholder="Nom d'utilisateur" value={username} onChangeText={setUsername} style={styles.input} autoCapitalize="none" />
          <TextInput placeholder="Mot de passe" value={password} onChangeText={setPassword} style={styles.input} secureTextEntry />
          <TouchableOpacity style={styles.primaryButton} onPress={login}>
            <Text style={styles.buttonText}>Se connecter</Text>
          </TouchableOpacity>
          <Text style={styles.helpText}>Configurez l'URL API dans "Paramètres" après connexion si nécessaire.</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      <View style={styles.navbar}>
        {(['dashboard', 'expenses', 'planner', 'settings'] as Screen[]).map((screen) => (
          <TouchableOpacity key={screen} onPress={() => setActiveScreen(screen)}>
            <Text style={[styles.navItem, activeScreen === screen && styles.navItemActive]}>{screen}</Text>
          </TouchableOpacity>
        ))}
      </View>

      {activeScreen === 'dashboard' && (
        <ScrollView style={styles.section}>
          <Text style={styles.welcome}>Bienvenue {user?.fullname || user?.username}</Text>
          <View style={styles.statsGrid}>
            <Stat label="Budget total" value={`${(stats?.grand_total || totalByList).toFixed(2)} €`} />
            <Stat label="Total payé" value={`${(stats?.paid_total || 0).toFixed(2)} €`} />
            <Stat label="Reste" value={`${(stats?.unpaid_total || 0).toFixed(2)} €`} />
            <Stat label="Progression" value={`${(stats?.payment_percentage || 0).toFixed(1)} %`} />
          </View>
          <Text style={styles.sectionTitle}>Dépenses récentes</Text>
          {expenses.slice(0, 5).map((expense) => (
            <View key={expense.id} style={styles.expenseCard}>
              <View>
                <Text style={styles.expenseName}>{expense.name}</Text>
                <Text>{(expense.quantity * expense.unit_price * expense.frequency).toFixed(2)} €</Text>
              </View>
              <Text style={{ color: expense.paid ? '#2e7d32' : '#d84315' }}>{expense.paid ? 'Payé' : 'En attente'}</Text>
            </View>
          ))}
        </ScrollView>
      )}

      {activeScreen === 'expenses' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Ajouter une dépense</Text>
          <TextInput style={styles.input} placeholder="Nom" value={newExpense.name} onChangeText={(v) => setNewExpense((p) => ({ ...p, name: v }))} />
          <TextInput style={styles.input} placeholder="Prix unitaire" keyboardType="numeric" value={newExpense.unit_price} onChangeText={(v) => setNewExpense((p) => ({ ...p, unit_price: v }))} />
          <TouchableOpacity style={styles.primaryButton} onPress={addExpense}>
            <Text style={styles.buttonText}>Ajouter</Text>
          </TouchableOpacity>

          <FlatList
            data={expenses}
            keyExtractor={(item) => String(item.id)}
            renderItem={({ item }) => (
              <View style={styles.expenseCard}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.expenseName}>{item.name}</Text>
                  <Text>{(item.quantity * item.unit_price * item.frequency).toFixed(2)} €</Text>
                </View>
                <Switch value={Boolean(item.paid)} onValueChange={() => togglePaid(item.id)} />
                <TouchableOpacity onPress={() => removeExpense(item.id)}>
                  <Text style={styles.delete}>Supprimer</Text>
                </TouchableOpacity>
              </View>
            )}
          />
        </View>
      )}

      {activeScreen === 'planner' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Programmation de l'évènement</Text>
          {plannerTasks.map((task) => (
            <View key={task.id} style={styles.taskRow}>
              <Text style={[styles.taskText, task.done && styles.done]}>{task.title}</Text>
              <Switch value={task.done} onValueChange={(value) => persistTasks(plannerTasks.map((t) => (t.id === task.id ? { ...t, done: value } : t)))} />
            </View>
          ))}
        </View>
      )}

      {activeScreen === 'settings' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Paramètres techniques</Text>
          <TextInput style={styles.input} value={apiBase} onChangeText={setApiBase} placeholder="URL de base API" autoCapitalize="none" />
          <TouchableOpacity style={styles.primaryButton} onPress={saveSettings}>
            <Text style={styles.buttonText}>Sauvegarder</Text>
          </TouchableOpacity>
        </View>
      )}
    </SafeAreaView>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.statCard}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8f4fb' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  authCard: { margin: 20, padding: 20, backgroundColor: '#fff', borderRadius: 14, gap: 12 },
  title: { fontSize: 30, fontWeight: '700', color: '#8b4f8d' },
  subtitle: { color: '#555' },
  input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#ddd', borderRadius: 10, padding: 12, marginBottom: 8 },
  primaryButton: { backgroundColor: '#8b4f8d', padding: 12, borderRadius: 10, alignItems: 'center' },
  buttonText: { color: '#fff', fontWeight: '700' },
  helpText: { color: '#777', fontSize: 12 },
  navbar: { flexDirection: 'row', justifyContent: 'space-around', paddingVertical: 12, backgroundColor: '#fff' },
  navItem: { textTransform: 'capitalize', color: '#666' },
  navItemActive: { color: '#8b4f8d', fontWeight: '700' },
  section: { flex: 1, padding: 16 },
  welcome: { fontSize: 18, fontWeight: '600', marginBottom: 10 },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
  statCard: { width: '48%', backgroundColor: '#fff', padding: 12, borderRadius: 10 },
  statLabel: { color: '#666', marginBottom: 6 },
  statValue: { fontWeight: '700', color: '#8b4f8d' },
  sectionTitle: { fontSize: 18, fontWeight: '700', marginBottom: 10, color: '#333' },
  expenseCard: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  expenseName: { fontWeight: '600' },
  delete: { color: '#d84315', fontWeight: '600' },
  taskRow: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  taskText: { fontSize: 15 },
  done: { textDecorationLine: 'line-through', color: '#2e7d32' },
});
