import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput, TouchableOpacity, ActivityIndicator, Alert, Switch } from 'react-native';
import { Picker } from '@react-native-picker/picker';
import GameService from '../../services/GameService';
import { ScoreEntryDetails, PlayerScore } from '../../types';

const ScoreEntryScreen = ({ route, navigation }: any) => {
  const { gameId } = route.params;
  const [details, setDetails] = useState<ScoreEntryDetails | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    loadScores();
  }, [gameId]);

  const loadScores = async () => {
    try {
      const data = await GameService.getGameScores(gameId);
      setDetails(data);
      navigation.setOptions({ title: data.isRecorded ? 'View Scores' : 'Enter Scores' });
    } catch (error) {
      console.error('Failed to load scores', error);
      Alert.alert('Error', 'Could not load score details for this game.');
    } finally {
      setLoading(false);
    }
  };

  const handleStrokeChange = (playerIndex: number, holeIndex: number, value: string) => {
    if (!details) return;

    const newDetails = { ...details };
    const strokes = [...newDetails.playerScores[playerIndex].strokes];
    const val = value === '' ? null : parseInt(value, 10);
    
    if (val === null || (!isNaN(val) && val >= 0 && val <= 15)) {
      strokes[holeIndex] = val;
      newDetails.playerScores[playerIndex].strokes = strokes;
      setDetails(newDetails);
    }
  };

  const handleTeeChange = (playerIndex: number, teeId: number) => {
    if (!details) return;
    const newDetails = { ...details };
    newDetails.playerScores[playerIndex].currentTeeId = teeId;
    setDetails(newDetails);
  };

  const handlePlayedToggle = (playerIndex: number, value: boolean) => {
    if (!details) return;
    const newDetails = { ...details };
    newDetails.playerScores[playerIndex].isPlayed = value;
    setDetails(newDetails);
  };

  const handleSave = async () => {
    if (!details) return;
    setSaving(true);
    try {
      await GameService.saveGameScores(gameId, {
        playerScores: details.playerScores.map(ps => ({
          playerId: ps.playerId,
          isPlayed: ps.isPlayed,
          currentTeeId: ps.currentTeeId,
          strokes: ps.strokes
        }))
      });
      Alert.alert('Success', 'Scores saved successfully.', [
        { text: 'OK', onPress: () => navigation.goBack() }
      ]);
    } catch (error) {
      console.error('Failed to save scores', error);
      Alert.alert('Error', 'Failed to save scores. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <ActivityIndicator style={styles.centered} size="large" />;
  }

  if (!details) {
    return (
      <View style={styles.centered}>
        <Text>Could not load game details.</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      {details.playerScores.map((playerScore, pIdx) => (
        <View key={playerScore.playerId} style={styles.playerCard}>
          <View style={styles.playerHeader}>
            <Text style={styles.playerName}>{playerScore.playerName}</Text>
            <View style={styles.playedToggle}>
              <Text style={styles.playedLabel}>Played</Text>
              <Switch
                value={playerScore.isPlayed}
                onValueChange={(val) => handlePlayedToggle(pIdx, val)}
              />
            </View>
          </View>

          <View style={styles.teeContainer}>
            <Text style={styles.label}>Tee:</Text>
            <Picker
              selectedValue={playerScore.currentTeeId}
              onValueChange={(val) => handleTeeChange(pIdx, val)}
              style={styles.picker}
            >
              {playerScore.availableTees.map(tee => (
                <Picker.Item key={tee.id} label={tee.name} value={tee.id} />
              ))}
            </Picker>
          </View>

          <View style={styles.scoresGrid}>
            <View style={styles.gridHeader}>
              <Text style={[styles.gridCell, styles.headerCell]}>Hole</Text>
              {details.nines[0].holes.map(h => (
                <Text key={h.number} style={[styles.gridCell, styles.headerCell]}>{h.number}</Text>
              ))}
              <Text style={[styles.gridCell, styles.headerCell]}>Tot</Text>
            </View>
            <View style={styles.gridRow}>
              <Text style={[styles.gridCell, styles.labelCell]}>Par</Text>
              {details.nines[0].holes.map(h => (
                <Text key={h.number} style={styles.gridCell}>{h.par}</Text>
              ))}
              <Text style={styles.gridCell}>{details.nines[0].holes.reduce((sum, h) => sum + h.par, 0)}</Text>
            </View>
            <View style={styles.gridRow}>
              <Text style={[styles.gridCell, styles.labelCell]}>Score</Text>
              {playerScore.strokes.map((s, hIdx) => (
                <TextInput
                  key={hIdx}
                  style={[styles.gridCell, styles.scoreInput]}
                  keyboardType="numeric"
                  value={s === null ? '' : s.toString()}
                  onChangeText={(val) => handleStrokeChange(pIdx, hIdx, val)}
                  maxLength={2}
                />
              ))}
              <Text style={styles.gridCell}>
                {playerScore.strokes.reduce((sum, s) => sum + (s || 0), 0)}
              </Text>
            </View>
          </View>
        </View>
      ))}

      <TouchableOpacity
        style={[styles.saveButton, saving && styles.disabledButton]}
        onPress={handleSave}
        disabled={saving}
      >
        {saving ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.saveButtonText}>Save Scores</Text>
        )}
      </TouchableOpacity>
      <View style={{ height: 40 }} />
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5', padding: 10 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  playerCard: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 15,
    marginBottom: 20,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.2,
    shadowRadius: 2,
  },
  playerHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
    paddingBottom: 5,
  },
  playerName: { fontSize: 18, fontWeight: 'bold' },
  playedToggle: { flexDirection: 'row', alignItems: 'center' },
  playedLabel: { marginRight: 5, fontSize: 12, color: '#666' },
  teeContainer: { flexDirection: 'row', alignItems: 'center', marginBottom: 15 },
  label: { fontSize: 14, fontWeight: '600', marginRight: 10 },
  picker: { flex: 1, height: 50 },
  scoresGrid: { borderWidth: 1, borderColor: '#ccc' },
  gridHeader: { flexDirection: 'row', backgroundColor: '#f0f0f0' },
  gridRow: { flexDirection: 'row', borderTopWidth: 1, borderTopColor: '#ccc' },
  gridCell: {
    flex: 1,
    padding: 5,
    textAlign: 'center',
    borderRightWidth: 1,
    borderRightColor: '#ccc',
    fontSize: 12,
    minWidth: 25,
  },
  headerCell: { fontWeight: 'bold' },
  labelCell: { backgroundColor: '#f9f9f9', fontWeight: '600' },
  scoreInput: {
    backgroundColor: '#fff',
    color: '#007AFF',
    fontWeight: 'bold',
    padding: 2,
  },
  saveButton: {
    backgroundColor: '#34C759',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 20,
  },
  disabledButton: { backgroundColor: '#ccc' },
  saveButtonText: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
});

export default ScoreEntryScreen;
